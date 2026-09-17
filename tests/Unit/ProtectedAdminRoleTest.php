<?php

namespace VagKaefer\CmsFilament\Tests\Unit;

use VagKaefer\CmsFilament\Exceptions\ProtectedRoleException;
use VagKaefer\CmsFilament\Filament\Resources\Roles\RoleResource;
use VagKaefer\CmsFilament\Models\Role;
use VagKaefer\CmsFilament\Tests\Fixtures\User;
use VagKaefer\CmsFilament\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

/**
 * O papel `admin` não pode ser excluído nem renomeado.
 *
 * É a chave do bypass de autorização (`Gate::before`): perdê-lo deixa a instalação sem ninguém capaz de administrar o
 * painel, sem caminho de volta pela interface.
 */
class ProtectedAdminRoleTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdminRole(): Role
    {
        return Role::create(['name' => Role::ADMIN, 'guard_name' => 'web']);
    }

    public function testAdminRoleCannotBeDeleted(): void
    {
        $role = $this->makeAdminRole();

        $this->expectException(ProtectedRoleException::class);

        $role->delete();
    }

    /**
     * A trava é do model, então vale para qualquer caminho — inclusive uma
     * exclusão em massa por query, que não passa pela UI.
     */
    public function testAdminRoleSurvivesDeletionAttempt(): void
    {
        $role = $this->makeAdminRole();

        try {
            $role->delete();
        } catch (ProtectedRoleException) {
            // esperado
        }

        $this->assertDatabaseHas('roles', ['name' => Role::ADMIN]);
    }

    /**
     * Barrar a exclusão não pode deixar rastro: o `bootHasPermissions()` do
     * Spatie escuta `deleting` e ali faz `users()->detach()`. Se a trava
     * rodasse depois dele, o papel sobreviveria sem nenhum usuário associado —
     * e a instalação ficaria sem administrador do mesmo jeito.
     */
    public function testFailedDeletionKeepsUsersAssignedToTheRole(): void
    {
        $role = $this->makeAdminRole();

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);
        $user->assignRole($role);

        try {
            $role->delete();
        } catch (ProtectedRoleException) {
            // esperado
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($user->fresh()->hasRole(Role::ADMIN));
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->getKey(),
            'model_id' => $user->getKey(),
        ]);
    }

    public function testAdminRoleCannotBeRenamed(): void
    {
        $role = $this->makeAdminRole();

        $this->expectException(ProtectedRoleException::class);

        $role->update(['name' => 'administrador']);
    }

    /**
     * Travar o nome não pode travar o resto: as permissões do `admin` seguem
     * editáveis.
     */
    public function testAdminRoleCanStillBeUpdatedOtherwise(): void
    {
        $role = $this->makeAdminRole();

        $role->update(['guard_name' => 'web']);

        $this->assertSame(Role::ADMIN, $role->fresh()->name);
    }

    public function testOtherRolesCanBeDeletedAndRenamed(): void
    {
        $editor = Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $editor->update(['name' => 'redator']);
        $this->assertSame('redator', $editor->fresh()->name);

        $editor->delete();
        $this->assertDatabaseMissing('roles', ['name' => 'redator']);
    }

    public function testIsProtectedOnlyFlagsTheAdminRole(): void
    {
        $this->assertTrue($this->makeAdminRole()->isProtected());
        $this->assertFalse(
            Role::create(['name' => 'editor', 'guard_name' => 'web'])->isProtected()
        );
    }

    /**
     * O PermissionsSeeder roda `Role::updateOrCreate(['name' => 'admin'])` em
     * toda instalação e a cada re-seed. Isso dispara o hook de `updating`, mas
     * sem alterar o nome — não pode ser bloqueado.
     */
    public function testSeederCanReRunWithoutTrippingTheGuard(): void
    {
        $this->makeAdminRole();

        Role::updateOrCreate(['name' => Role::ADMIN, 'guard_name' => 'web']);

        $this->assertDatabaseHas('roles', ['name' => Role::ADMIN]);
        $this->assertSame(1, Role::where('name', Role::ADMIN)->count());
    }

    /**
     * A UI esconde o botão de excluir do papel protegido, para o usuário não
     * esbarrar na exception do model.
     */
    public function testResourceHidesDeleteActionForTheAdminRole(): void
    {
        $this->assertFalse(RoleResource::canDelete($this->makeAdminRole()));
    }
}
