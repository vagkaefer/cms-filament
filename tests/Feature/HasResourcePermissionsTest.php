<?php

namespace VagKaefer\CmsFilament\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use VagKaefer\CmsFilament\Models\Permission;
use VagKaefer\CmsFilament\Models\Role;
use VagKaefer\CmsFilament\Tests\Fixtures\Resources\SupporterResource;
use VagKaefer\CmsFilament\Tests\Fixtures\User;
use VagKaefer\CmsFilament\Tests\TestCase;

/**
 * A autorização dos Resources: permissão explícita libera a ação e o cargo
 * administrativo passa em tudo pelo `Gate::before`.
 */
class HasResourcePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'Redator',
            'email' => 'redator@example.com',
            'password' => 'secret',
        ]);
    }

    private function forgetCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function testVisitorWithoutPermissionIsDenied(): void
    {
        Auth::login($this->user());

        $this->assertFalse(SupporterResource::canViewAny());
        $this->assertFalse(SupporterResource::canCreate());
    }

    public function testExplicitPermissionGrantsOnlyThatAction(): void
    {
        $user = $this->user();
        Permission::findOrCreate('view_supporter', 'web');
        $user->givePermissionTo('view_supporter');
        $this->forgetCache();

        Auth::login($user->fresh());

        $this->assertTrue(SupporterResource::canViewAny());
        $this->assertFalse(SupporterResource::canCreate());
    }

    /**
     * O bypass do cargo administrativo é o que dispensa criar permissão para
     * cada tela nova antes de o admin conseguir usá-la.
     */
    public function testAdminRoleBypassesEveryCheck(): void
    {
        $user = $this->user();
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $this->forgetCache();

        Auth::login($user->fresh());

        $this->assertTrue(SupporterResource::canViewAny());
        $this->assertTrue(SupporterResource::canCreate());
        $this->assertTrue(cms_filament_is_admin());
    }

    public function testGuestIsAlwaysDenied(): void
    {
        $this->assertFalse(SupporterResource::canViewAny());
        $this->assertFalse(cms_filament_is_admin());
    }
}
