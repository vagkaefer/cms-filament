<?php

namespace VagKaefer\CmsFilament\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use VagKaefer\CmsFilament\Database\Seeders\PermissionsSeeder;
use VagKaefer\CmsFilament\Tests\Fixtures\User;
use VagKaefer\CmsFilament\Tests\TestCase;

/**
 * O seeder é o passo de instalação que deixa a instalação com administrador.
 * Sem e-mail configurado ele não inventa ninguém; com e-mail, é idempotente.
 */
class PermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    private function runSeeder(): void
    {
        (new PermissionsSeeder())->run();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function testCreatesAdminRoleWithTheExtraPermissions(): void
    {
        $this->runSeeder();

        $this->assertDatabaseHas('roles', ['name' => 'admin', 'guard_name' => 'web']);

        foreach (['create-backup', 'download-backup', 'delete-backup'] as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission]);
        }
    }

    public function testAssignsTheAdminRoleToConfiguredEmails(): void
    {
        $user = User::create([
            'name' => 'Dona do site',
            'email' => 'dona@example.com',
            'password' => 'secret',
        ]);

        config(['cms-filament.admin_emails' => ['dona@example.com']]);

        $this->runSeeder();

        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function testDoesNotInventAnAdminWhenNoEmailIsConfigured(): void
    {
        $user = User::create([
            'name' => 'Qualquer',
            'email' => 'qualquer@example.com',
            'password' => 'secret',
        ]);

        config(['cms-filament.admin_emails' => []]);

        $this->runSeeder();

        $this->assertFalse($user->fresh()->hasRole('admin'));
    }

    public function testCanRunTwice(): void
    {
        config(['cms-filament.admin_emails' => []]);

        $this->runSeeder();
        $this->runSeeder();

        $this->assertSame(1, \VagKaefer\CmsFilament\Models\Role::where('name', 'admin')->count());
    }
}
