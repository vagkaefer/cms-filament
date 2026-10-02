<?php

namespace VagKaefer\CmsFilament;

use AchyutN\FilamentLogViewer\FilamentLogViewer;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Auth\Pages\EditProfile as FilamentEditProfile;
use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;
use Spatie\LaravelPasskeys\Http\Controllers\GeneratePasskeyAuthenticationOptionsController;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use VagKaefer\CmsFilament\Filament\Pages\Auth\EditProfile;
use VagKaefer\CmsFilament\Filament\Resources\Audits\AuditResource;
use VagKaefer\CmsFilament\Filament\Resources\Configurations\ConfigurationResource;
use VagKaefer\CmsFilament\Filament\Resources\Permissions\PermissionResource;
use VagKaefer\CmsFilament\Filament\Resources\Roles\RoleResource;
use VagKaefer\CmsFilament\Filament\Resources\Users\UserResource;
use VagKaefer\CmsFilament\Filament\Widgets\CmsVersionWidget;
use VagKaefer\CmsFilament\Http\Controllers\PasskeyLoginController;

/**
 * Registra as telas administrativas do package em um painel do projeto.
 *
 * O package não define painel próprio: quem manda em id, path, cores e rotas é
 * o projeto consumidor. Basta acrescentar o plugin ao painel existente:
 *
 *     $panel->plugin(CmsFilamentPlugin::make())
 *
 * Todos os componentes opcionais têm método fluente para desligar, por exemplo
 * `->backups(false)`.
 */
class CmsFilamentPlugin implements Plugin
{
    protected bool $backups = true;

    protected bool $environmentIndicator = true;

    protected bool $footer = false;

    protected bool $logViewer = true;

    protected bool $multiFactorAuthentication = true;

    protected bool $passkeys = true;

    protected bool $profile = true;

    protected bool $userExport = false;

    protected bool $versionWidget = false;

    protected ?string $navigationGroup = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'cms-filament';
    }

    /**
     * Resources entregues pelo package.
     *
     * A lista é estática (e não descoberta por diretório) para que o comando
     * de geração de permissões a enxergue mesmo sem um painel montado.
     *
     * @return array<int, class-string<\Filament\Resources\Resource>>
     */
    public static function resources(): array
    {
        return [
            UserResource::class,
            RoleResource::class,
            PermissionResource::class,
            AuditResource::class,
            ConfigurationResource::class,
        ];
    }

    public function backups(bool $condition = true): static
    {
        $this->backups = $condition;

        return $this;
    }

    public function environmentIndicator(bool $condition = true): static
    {
        $this->environmentIndicator = $condition;

        return $this;
    }

    public function footer(bool $condition = true): static
    {
        $this->footer = $condition;

        return $this;
    }

    public function logViewer(bool $condition = true): static
    {
        $this->logViewer = $condition;

        return $this;
    }

    public function multiFactorAuthentication(bool $condition = true): static
    {
        $this->multiFactorAuthentication = $condition;

        return $this;
    }

    public function passkeys(bool $condition = true): static
    {
        $this->passkeys = $condition;

        return $this;
    }

    public function profile(bool $condition = true): static
    {
        $this->profile = $condition;

        return $this;
    }

    public function userExport(bool $condition = true): static
    {
        $this->userExport = $condition;

        return $this;
    }

    public function versionWidget(bool $condition = true): static
    {
        $this->versionWidget = $condition;

        return $this;
    }

    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function hasUserExport(): bool
    {
        return $this->userExport;
    }

    /**
     * Se o login por passkey está ligado.
     *
     * Além do toggle, o model de usuário precisa declarar `HasPasskeys`. Assim
     * um projeto que atualiza o package sem declarar o contrato continua como
     * estava, em vez de quebrar no login.
     */
    public function hasPasskeys(): bool
    {
        return $this->passkeys && is_subclass_of(cms_filament_user_model(), HasPasskeys::class);
    }

    public function register(Panel $panel): void
    {
        if ($this->navigationGroup !== null) {
            config(['cms-filament.navigation_group' => $this->navigationGroup]);
        }

        $group = (string) config('cms-filament.navigation_group', 'Administração');

        $panel
            ->resources(static::resources())
            ->navigationGroups([
                NavigationGroup::make()->label($group)->collapsible(),
            ]);

        if ($this->versionWidget) {
            $panel->widgets([CmsVersionWidget::class]);
        }

        if ($this->footer) {
            $panel->renderHook(
                PanelsRenderHook::FOOTER,
                // @phpstan-ignore-next-line argument.type (view registrada pelo ServiceProvider)
                fn (): View => view('cms-filament::filament.footer'),
            );
        }

        if ($this->logViewer) {
            $panel->plugin(
                FilamentLogViewer::make()
                    ->authorize(fn (): bool => cms_filament_is_admin())
                    ->navigationGroup($group)
                    ->navigationIcon('heroicon-o-document-text')
                    ->navigationLabel('Logs')
                    ->navigationSort(80)
                    ->pollingTime('10')
            );
        }

        if ($this->backups) {
            $panel->plugin(
                FilamentSpatieLaravelBackupPlugin::make()
                    ->authorize(fn (): bool => cms_filament_is_admin())
                    ->navigationGroup($group)
                    ->navigationLabel('Backups')
                    ->navigationSort(90)
                    ->usingPollingInterval('15s')
            );
        }

        if ($this->environmentIndicator) {
            $panel->plugin(
                EnvironmentIndicatorPlugin::make()
                    ->visible(fn (): bool => cms_filament_is_admin())
                    ->color(fn () => match (app()->environment()) {
                        'production' => Color::Green,
                        'staging' => Color::Orange,
                        'local' => Color::Blue,
                        default => Color::Stone,
                    })
                    ->showBorder(false)
            );
        }

        if ($this->multiFactorAuthentication) {
            $panel->multiFactorAuthentication([
                AppAuthentication::make()->recoverable(),
                EmailAuthentication::make(),
            ]);
        }

        $passkeys = $this->hasPasskeys();

        if ($passkeys) {
            $this->registerPasskeys($panel);
        }

        // A gestão do segundo fator e das passkeys vive na página de perfil:
        // sem ela o usuário não teria onde cadastrar o app autenticador nem a
        // passkey.
        if ($this->profile && ($this->multiFactorAuthentication || $passkeys) && ! $panel->hasProfile()) {
            $panel->profile($passkeys ? EditProfile::class : FilamentEditProfile::class);
        }
    }

    /**
     * Login por passkey: rotas públicas do painel e o botão na tela de login.
     *
     * As rotas ficam dentro do painel (`/painel/passkeys/...`), com os
     * middlewares dele, para que o login entre pelo guard certo. O throttle
     * limita quem tenta adivinhar credenciais.
     */
    protected function registerPasskeys(Panel $panel): void
    {
        $panel
            ->routes(fn () => Route::prefix('passkeys')
                ->name('cms-filament.passkeys.')
                ->middleware('throttle:10,1')
                ->group(function (): void {
                    Route::get('opcoes', GeneratePasskeyAuthenticationOptionsController::class)->name('options');
                    Route::post('entrar', PasskeyLoginController::class)->name('login');
                }))
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                // @phpstan-ignore-next-line argument.type (view registrada pelo ServiceProvider)
                fn (): View => view('cms-filament::passkeys.login'),
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
