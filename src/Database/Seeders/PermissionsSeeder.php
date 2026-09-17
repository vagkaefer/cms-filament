<?php

namespace VagKaefer\CmsFilament\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\PermissionRegistrar;
use VagKaefer\CmsFilament\Models\Permission;
use VagKaefer\CmsFilament\Models\Role;

/**
 * Cria o cargo administrativo, as permissões avulsas e as dos Resources.
 *
 * Idempotente: pode rodar a cada deploy. Os administradores saem de
 * `cms-filament.admin_emails` (env `CMS_ADMIN_EMAILS`), então o package não
 * carrega e-mail de ninguém.
 */
class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $roleName = (string) config('cms-filament.admin_role', 'admin');
        $adminRole = Role::findOrCreate($roleName, 'web');

        /** @var array<int, string> $extraPermissions */
        $extraPermissions = config('cms-filament.extra_permissions', []);

        foreach ($extraPermissions as $permissionName) {
            $permission = Permission::findOrCreate($permissionName, 'web');
            $adminRole->givePermissionTo($permission);
        }

        $this->info('Gerando permissões dos Filament Resources...');

        Artisan::call('permissions:generate-resources');

        $output = Artisan::output();

        if ($output !== '') {
            $this->info($output);
        }

        $this->assignAdmins($adminRole);

        // O cache de permissões é consultado em toda checagem; sem limpar, as
        // permissões recém-criadas só valeriam no próximo ciclo de cache.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Mensagem informativa, quando o seeder roda pelo console.
     *
     * `$this->command` é nulo ao rodar programaticamente (em testes, por
     * exemplo), e aí não há para onde escrever.
     */
    protected function info(string $message): void
    {
        // A propriedade só é preenchida quando o seeder roda pelo console.
        // @phpstan-ignore-next-line isset.property
        if (isset($this->command)) {
            $this->command->line($message);
        }
    }

    protected function warn(string $message): void
    {
        // @phpstan-ignore-next-line isset.property
        if (isset($this->command)) {
            $this->command->warn($message);
        }
    }

    /**
     * Concede o cargo administrativo aos e-mails configurados.
     */
    protected function assignAdmins(RoleContract $adminRole): void
    {
        /** @var array<int, string> $emails */
        $emails = config('cms-filament.admin_emails', []);

        if ($emails === []) {
            $this->warn(
                'Nenhum administrador definido. Preencha CMS_ADMIN_EMAILS no .env e rode o seeder de novo.'
            );

            return;
        }

        $model = cms_filament_user_model();

        foreach ($emails as $email) {
            $user = $model::query()->where('email', $email)->first();

            if ($user === null || ! method_exists($user, 'assignRole')) {
                $this->warn("Usuário não encontrado para o e-mail {$email}; cargo não atribuído.");

                continue;
            }

            $user->assignRole($adminRole);
            $this->info("Cargo {$adminRole->name} atribuído a {$email}.");
        }
    }
}
