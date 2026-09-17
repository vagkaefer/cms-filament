<?php

namespace VagKaefer\CmsFilament\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use OwenIt\Auditing\AuditingServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use VagKaefer\CmsFilament\Providers\CmsFilamentServiceProvider;
use VagKaefer\CmsFilament\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    /**
     * Todos os providers de que o package depende são declarados aqui: o
     * Testbench não faz a descoberta automática do Composer, então sem esta
     * lista faltariam o binding `filament` e a checagem de permissões do
     * Spatie (que pluga no Gate).
     *
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            PermissionServiceProvider::class,
            AuditingServiceProvider::class,
            CmsFilamentServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Os casts de MFA são criptografados: sem APP_KEY o model nem carrega.
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        // O package lê o model de usuário do guard; nos testes é a fixture,
        // com id inteiro, igual a um app Laravel padrão.
        $app['config']->set('auth.providers.users.model', User::class);
    }

    /**
     * Roda as migrations padrão do Laravel (users, cache, jobs) que o Testbench
     * distribui.
     *
     * Elas precisam existir antes das do package, que acrescentam colunas em
     * `users`. O prefixo 0001_ das migrations do Laravel garante essa ordem.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(static::laravelMigrationsPath());
    }

    /**
     * Migrations padrão que o Testbench distribui (users, cache, jobs).
     */
    protected static function laravelMigrationsPath(): string
    {
        return dirname(__DIR__) . '/vendor/orchestra/testbench-core/laravel/migrations';
    }
}
