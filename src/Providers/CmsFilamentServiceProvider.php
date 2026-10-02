<?php

namespace VagKaefer\CmsFilament\Providers;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\LaravelPasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction as SpatieCeremonyAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction as SpatieRegisterOptionsAction;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use VagKaefer\CmsFilament\Actions\Passkeys\ConfigurePasskeyCeremonies;
use VagKaefer\CmsFilament\Actions\Passkeys\GeneratePasskeyRegisterOptions;
use VagKaefer\CmsFilament\Console\Commands\AiSetupCommand;
use VagKaefer\CmsFilament\Console\Commands\CleanLargeLogFile;
use VagKaefer\CmsFilament\Console\Commands\CleanOldLogs;
use VagKaefer\CmsFilament\Console\Commands\GenerateResourcePermissions;
use VagKaefer\CmsFilament\Livewire\PasskeysManager;
use VagKaefer\CmsFilament\Models\Audit;
use VagKaefer\CmsFilament\Models\Concerns\CmsUser;
use VagKaefer\CmsFilament\Models\Permission;
use VagKaefer\CmsFilament\Models\Role;
use VagKaefer\CmsFilament\Observers\UserObserver;

class CmsFilamentServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/cms-filament.php', 'cms-filament');

        // As configs de permissão e auditoria também são fundidas aqui para
        // que as migrations do package funcionem sem o projeto ter publicado
        // nada: os providers de origem só fundem as suas no boot, tarde demais
        // para uma migration que roda antes.
        $this->mergeConfigFrom(__DIR__ . '/../Config/permission.php', 'permission');
        $this->mergeConfigFrom(__DIR__ . '/../Config/audit.php', 'audit');

        $this->overrideVendorModelDefaults();
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'cms-filament');

        $this->registerAdminGate();
        $this->registerUserObserver();
        $this->registerPasskeys();

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
            $this->registerPublishing();
            $this->registerCommands();
        }
    }

    /**
     * Aponta os models de Role/Permission/Audit dos packages de terceiros para
     * os deste package.
     *
     * Feito com `config()->set` — e não com `mergeConfigFrom` — porque o merge
     * é raso e "o valor existente vence": dependendo da ordem de registro dos
     * providers, o default do vendor já estaria em `config` e o nosso seria
     * ignorado. A troca só acontece quando a chave ainda está no default do
     * vendor, então uma config publicada e customizada pelo projeto é mantida.
     */
    protected function overrideVendorModelDefaults(): void
    {
        $defaults = [
            'permission.models.role' => [\Spatie\Permission\Models\Role::class, Role::class],
            'permission.models.permission' => [\Spatie\Permission\Models\Permission::class, Permission::class],
            'audit.implementation' => [\OwenIt\Auditing\Models\Audit::class, Audit::class],
        ];

        foreach ($defaults as $key => [$vendorDefault, $ours]) {
            $current = config($key);

            if ($current === null || $current === $vendorDefault) {
                config([$key => $ours]);
            }
        }
    }

    /**
     * Bypass de autorização para o cargo administrativo.
     *
     * O `method_exists` cobre instalações com mais de um guard, onde o usuário
     * autenticado pode não usar a trait de cargos do Spatie.
     */
    protected function registerAdminGate(): void
    {
        // Registrado via callAfterResolving, e não pela facade: resolver o Gate
        // aqui no boot o instanciaria antes do provider do Spatie, que também
        // espera o Gate para plugar a checagem de permissões — e ela ficaria
        // de fora, fazendo can() sempre devolver false.
        $this->callAfterResolving(Gate::class, function (Gate $gate): void {
            $gate->before(function (Authenticatable $user, string $ability): ?bool {
                if (! method_exists($user, 'hasRole')) {
                    return null;
                }

                return $user->hasRole(config('cms-filament.admin_role', 'admin')) ? true : null;
            });
        });
    }

    /**
     * Observa o model de usuário do projeto, se ele usar a trait do package.
     *
     * O observer encerra as demais sessões quando a senha muda. Sem a trait o
     * model não é "do package" e o observer não é registrado.
     */
    protected function registerUserObserver(): void
    {
        $model = cms_filament_user_model();

        if (! class_exists($model)) {
            return;
        }

        if (! in_array(CmsUser::class, class_uses_recursive($model), true)) {
            return;
        }

        $model::observe(UserObserver::class);
    }

    /**
     * Login por passkey (spatie/laravel-passkeys).
     *
     * A config do spatie é ajustada aqui, no boot, e não no register: o
     * provider dele funde a config dele no register, e um merge raso feito
     * antes trocaria o array inteiro de `actions` ou `relying_party` pelo nosso.
     *
     * Cada chave só é trocada enquanto está no default, então uma config
     * `passkeys.php` publicada e customizada pelo projeto é mantida.
     */
    protected function registerPasskeys(): void
    {
        Livewire::component('cms-filament.passkeys-manager', PasskeysManager::class);

        // O @simplewebauthn/browser vai pronto no package (UMD, expõe
        // window.SimpleWebAuthnBrowser): sem build de JS aqui nem no projeto.
        // O `filament:upgrade` do composer o publica em public/js.
        FilamentAsset::register([
            Js::make('passkeys', __DIR__ . '/../Resources/dist/simplewebauthn-browser.umd.min.js'),
        ], package: 'vagkaefer/cms-filament');

        $model = cms_filament_user_model();

        if (! is_subclass_of($model, HasPasskeys::class)) {
            return;
        }

        $authenticatable = config('passkeys.models.authenticatable');

        if (! is_string($authenticatable) || ! is_subclass_of($authenticatable, HasPasskeys::class)) {
            config(['passkeys.models.authenticatable' => $model]);
        }

        $relyingPartyId = config('cms-filament.passkeys.rp_id');

        if (filled($relyingPartyId)) {
            config(['passkeys.relying_party.id' => $relyingPartyId]);
        }

        $actions = [
            'configure_ceremony_step_manager_factory' => [SpatieCeremonyAction::class, ConfigurePasskeyCeremonies::class],
            'generate_passkey_register_options' => [SpatieRegisterOptionsAction::class, GeneratePasskeyRegisterOptions::class],
        ];

        foreach ($actions as $name => [$vendorDefault, $ours]) {
            $current = config("passkeys.actions.{$name}");

            if ($current === null || $current === $vendorDefault) {
                config(["passkeys.actions.{$name}" => $ours]);
            }
        }
    }

    /**
     * Arquivos publicáveis pelo projeto consumidor.
     */
    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/cms-filament.php' => config_path('cms-filament.php'),
            __DIR__ . '/../Config/permission.php' => config_path('permission.php'),
            __DIR__ . '/../Config/audit.php' => config_path('audit.php'),
            __DIR__ . '/../Config/backup.php' => config_path('backup.php'),
        ], 'cms-filament-config');

        $this->publishes([
            __DIR__ . '/../Database/Migrations' => database_path('migrations'),
        ], 'cms-filament-migrations');
    }

    /**
     * Comandos de console do package.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            GenerateResourcePermissions::class,
            CleanLargeLogFile::class,
            CleanOldLogs::class,
            AiSetupCommand::class,
        ]);
    }
}
