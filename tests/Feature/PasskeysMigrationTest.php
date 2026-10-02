<?php

namespace VagKaefer\CmsFilament\Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use VagKaefer\CmsFilament\Tests\Fixtures\Passkeys;
use VagKaefer\CmsFilament\Tests\Fixtures\User;
use VagKaefer\CmsFilament\Tests\TestCase;

/**
 * A tabela `passkeys` acompanha o `users` do projeto e precisa tolerar um
 * projeto que já a tenha criado com o stub do spatie.
 */
class PasskeysMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): Migration
    {
        return require __DIR__ . '/../../src/Database/Migrations/2026_10_01_000000_create_passkeys_table.php';
    }

    public function testCreatesThePasskeysTable(): void
    {
        foreach (['id', 'authenticatable_id', 'name', 'credential_id', 'data', 'last_used_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('passkeys', $column), "Coluna {$column} não foi criada.");
        }
    }

    public function testPasskeysGoAwayWithTheUser(): void
    {
        $foreignKey = collect(Schema::getForeignKeys('passkeys'))
            ->firstWhere('columns', ['authenticatable_id']);

        $this->assertNotNull($foreignKey);
        $this->assertSame('users', $foreignKey['foreign_table']);
        $this->assertSame('cascade', strtolower($foreignKey['on_delete']));
    }

    public function testPasskeyBelongsToTheUserWithIntegerId(): void
    {
        $user = User::create(['name' => 'Redator', 'email' => 'redator@exemplo.com', 'password' => 'secret']);

        $this->assertTrue(Passkeys::create($user)->authenticatable->is($user));
    }

    public function testRunningItAgainIsHarmless(): void
    {
        $this->migration()->up();

        $this->assertTrue(Schema::hasTable('passkeys'));
    }

    public function testDownDropsTheTable(): void
    {
        $this->migration()->down();

        $this->assertFalse(Schema::hasTable('passkeys'));
    }
}
