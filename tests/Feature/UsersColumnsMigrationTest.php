<?php

namespace VagKaefer\CmsFilament\Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use VagKaefer\CmsFilament\Tests\TestCase;

/**
 * A migration acrescenta colunas a uma tabela que é do projeto, não do package.
 * Ela precisa tolerar rodar de novo — e rodar num projeto que já tenha parte
 * das colunas — sem estourar.
 */
class UsersColumnsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const COLUMNS = [
        'active',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
        'has_email_authentication',
    ];

    private function migration(): Migration
    {
        return require __DIR__ . '/../../src/Database/Migrations/2025_11_20_000000_add_cms_filament_columns_to_users_table.php';
    }

    public function testAddsEveryColumnToTheUsersTable(): void
    {
        foreach (self::COLUMNS as $column) {
            $this->assertTrue(
                Schema::hasColumn('users', $column),
                "Coluna {$column} não foi criada."
            );
        }
    }

    public function testRunningItAgainIsHarmless(): void
    {
        $this->migration()->up();

        foreach (self::COLUMNS as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column));
        }
    }

    public function testDownRemovesOnlyTheColumnsItAdded(): void
    {
        $this->migration()->down();

        foreach (self::COLUMNS as $column) {
            $this->assertFalse(Schema::hasColumn('users', $column));
        }

        $this->assertTrue(Schema::hasColumn('users', 'email'));

        // Reverter duas vezes também não pode quebrar.
        $this->migration()->down();
        $this->assertFalse(Schema::hasColumn('users', 'active'));
    }
}
