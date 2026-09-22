<?php

namespace VagKaefer\CmsFilament\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use VagKaefer\CmsFilament\Filament\Resources\Audits\AuditResource;
use VagKaefer\CmsFilament\Filament\Resources\Users\UserResource;
use VagKaefer\CmsFilament\Models\Audit;
use VagKaefer\CmsFilament\Models\Role;
use VagKaefer\CmsFilament\Tests\Fixtures\User;
use VagKaefer\CmsFilament\Tests\TestCase;

/**
 * As contas dos domínios protegidos só existem para elas mesmas: nem o cargo
 * administrativo do projeto consumidor as enxerga ou administra.
 */
class ProtectedUsersResourceTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $name = 'Usuário'): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'secret',
        ]);
    }

    private function admin(string $email = 'admin@exemplo.com'): User
    {
        $user = $this->user($email, 'Administrador');
        Role::findOrCreate(config('cms-filament.admin_role'), 'web');
        $user->assignRole(config('cms-filament.admin_role'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    /**
     * @return array<int, string>
     */
    private function visibleEmails(): array
    {
        return UserResource::getEloquentQuery()->pluck('email')->all();
    }

    public function testAdminDoesNotSeeProtectedAccounts(): void
    {
        $admin = $this->admin();
        $this->user('suporte@cloudger.com.br', 'Suporte');
        $this->user('redator@exemplo.com', 'Redator');

        Auth::login($admin);

        $visible = $this->visibleEmails();

        $this->assertNotContains('suporte@cloudger.com.br', $visible);
        $this->assertContains('admin@exemplo.com', $visible);
        $this->assertContains('redator@exemplo.com', $visible, 'A regra não pode esconder usuários comuns.');
    }

    public function testAdminCannotActOnProtectedAccount(): void
    {
        $admin = $this->admin();
        $protected = $this->user('suporte@cloudger.host', 'Suporte');
        $common = $this->user('redator@exemplo.com', 'Redator');

        Auth::login($admin);

        $this->assertFalse(UserResource::canView($protected));
        $this->assertFalse(UserResource::canEdit($protected));
        $this->assertFalse(UserResource::canDelete($protected));

        $this->assertTrue(UserResource::canEdit($common), 'O admin segue administrando os demais.');
    }

    public function testProtectedUserSeesAndManagesOtherProtectedAccounts(): void
    {
        // Admin porque o que se afere aqui é a camada de proteção, não a de
        // permissão: sem cargo, qualquer usuário é barrado antes dela.
        $protected = $this->admin('vagner@kaefer.eng.br');
        $other = $this->user('suporte@cloudger.com.br', 'Suporte');

        Auth::login($protected);

        $visible = $this->visibleEmails();

        $this->assertContains('suporte@cloudger.com.br', $visible);
        $this->assertContains('vagner@kaefer.eng.br', $visible);

        $this->assertTrue(UserResource::canEdit($other));
        $this->assertTrue(UserResource::canDelete($other));
    }

    public function testProtectedUserManagesItself(): void
    {
        $protected = $this->admin('vagner@kaefer.eng.br');

        Auth::login($protected);

        $this->assertTrue(UserResource::canView($protected));
        $this->assertTrue(UserResource::canEdit($protected));
    }

    public function testAuditTrailHidesRowsOfProtectedAccounts(): void
    {
        $admin = $this->admin();
        $protected = $this->user('suporte@cloudger.com.br', 'Suporte');

        $this->audit($protected);
        $this->audit($admin);
        $this->audit(null);

        Auth::login($admin);

        $query = Audit::query();
        AuditResource::hideProtectedUsersTrail($query);

        $userIds = $query->pluck('user_id')->all();

        $this->assertNotContains($protected->getKey(), $userIds);
        $this->assertContains($admin->getKey(), $userIds);
        $this->assertContains(null, $userIds, 'Ações do sistema seguem visíveis.');
    }

    public function testAuditTrailKeepsEverythingForProtectedAccounts(): void
    {
        $protected = $this->admin('vagner@kaefer.eng.br');
        $other = $this->user('suporte@cloudger.com.br', 'Suporte');

        $this->audit($other);

        Auth::login($protected);

        $query = Audit::query();
        AuditResource::hideProtectedUsersTrail($query);

        $this->assertContains($other->getKey(), $query->pluck('user_id')->all());
    }

    private function audit(?User $user): Audit
    {
        return Audit::create([
            'user_type' => $user === null ? null : $user::class,
            'user_id' => $user?->getKey(),
            'event' => 'updated',
            'auditable_type' => User::class,
            'auditable_id' => '1',
            'old_values' => [],
            'new_values' => [],
        ]);
    }

    /**
     * Sem ninguém autenticado — console, filas — esconder é o padrão.
     */
    public function testHidesProtectedAccountsWithoutAuthenticatedUser(): void
    {
        $this->user('suporte@cloudger.com.br', 'Suporte');
        $this->user('redator@exemplo.com', 'Redator');

        $visible = $this->visibleEmails();

        $this->assertNotContains('suporte@cloudger.com.br', $visible);
        $this->assertContains('redator@exemplo.com', $visible);
    }
}
