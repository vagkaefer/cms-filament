<?php

namespace VagKaefer\CmsFilament\Tests\Feature;

use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use VagKaefer\CmsFilament\Models\Role;
use VagKaefer\CmsFilament\Tests\Fixtures\User;
use VagKaefer\CmsFilament\Tests\TestCase;

/**
 * A trait é o único ponto de contato entre o package e o model de usuário do
 * projeto: ela precisa entregar cargos, segundo fator e a flag de conta ativa
 * sem exigir que o projeto altere casts ou fillable.
 */
class CmsUserTraitTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Pessoa',
            'email' => 'pessoa@example.com',
            'password' => 'secret',
        ], $attributes));
    }

    public function testAssignsRoles(): void
    {
        $user = $this->user();
        $user->assignRole(Role::findOrCreate('editor', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($user->fresh()->hasRole('editor'));
    }

    /**
     * O segredo do app autenticador vale tanto quanto uma senha: precisa sair
     * criptografado do banco e voltar legível pelo getter.
     */
    public function testAppAuthenticationSecretIsStoredEncrypted(): void
    {
        $user = $this->user();
        $user->saveAppAuthenticationSecret('SEGREDO123');

        $raw = DB::table('users')->where('id', $user->getKey())->value('app_authentication_secret');

        $this->assertNotSame('SEGREDO123', $raw);
        $this->assertSame('SEGREDO123', $user->fresh()->getAppAuthenticationSecret());
        $this->assertSame($user->email, $user->getAppAuthenticationHolderName());
    }

    public function testRecoveryCodesRoundTrip(): void
    {
        $user = $this->user();
        $codes = ['aaa-111', 'bbb-222'];

        $user->saveAppAuthenticationRecoveryCodes($codes);

        $raw = DB::table('users')->where('id', $user->getKey())->value('app_authentication_recovery_codes');

        $this->assertNotSame(json_encode($codes), $raw);
        $this->assertSame($codes, $user->fresh()->getAppAuthenticationRecoveryCodes());
    }

    public function testEmailAuthenticationToggles(): void
    {
        $user = $this->user();

        $this->assertFalse($user->hasEmailAuthentication());

        $user->toggleEmailAuthentication(true);

        $this->assertTrue($user->fresh()->hasEmailAuthentication());
    }

    public function testInactiveAccountCannotAccessThePanel(): void
    {
        $panel = Panel::make()->id('painel-' . uniqid())->path('painel');

        $active = $this->user();
        $this->assertTrue($active->isActive());
        $this->assertTrue($active->canAccessPanel($panel));

        $inactive = $this->user(['email' => 'inativo@example.com', 'active' => false]);

        $this->assertFalse($inactive->isActive());
        $this->assertFalse($inactive->canAccessPanel($panel));
        $this->assertFalse($inactive->canBeImpersonated());
    }

    public function testActiveIsCastToBooleanAndMassAssignable(): void
    {
        $user = $this->user(['active' => false]);

        $this->assertIsBool($user->fresh()->active);
        $this->assertFalse($user->fresh()->active);
    }

    public function testOnlyAdminCanImpersonate(): void
    {
        $user = $this->user();
        $this->assertFalse($user->canImpersonate());

        $user->assignRole(Role::findOrCreate('admin', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($user->fresh()->canImpersonate());
    }
}
