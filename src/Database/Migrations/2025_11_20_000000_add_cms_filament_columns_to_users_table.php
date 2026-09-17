<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Colunas que o package acrescenta ao `users` do projeto.
     *
     * Cada uma é checada individualmente para que a migration rode também em
     * um projeto que já tenha parte delas (um `active` próprio, por exemplo) e
     * possa ser reexecutada com segurança.
     *
     * @var array<string, callable(Blueprint): mixed>
     */
    private array $columns;

    public function __construct()
    {
        $this->columns = [
            'active' => fn (Blueprint $table) => $table
                ->boolean('active')
                ->default(true),
            'app_authentication_secret' => fn (Blueprint $table) => $table
                ->text('app_authentication_secret')
                ->nullable(),
            'app_authentication_recovery_codes' => fn (Blueprint $table) => $table
                ->text('app_authentication_recovery_codes')
                ->nullable(),
            'has_email_authentication' => fn (Blueprint $table) => $table
                ->boolean('has_email_authentication')
                ->default(false),
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table = $this->usersTable();

        if (! Schema::hasTable($table)) {
            return;
        }

        $missing = array_filter(
            $this->columns,
            fn (callable $definition, string $column): bool => ! Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_BOTH
        );

        if ($missing === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($missing): void {
            foreach ($missing as $definition) {
                $definition($blueprint);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = $this->usersTable();

        if (! Schema::hasTable($table)) {
            return;
        }

        $existing = array_values(array_filter(
            array_keys($this->columns),
            fn (string $column): bool => Schema::hasColumn($table, $column)
        ));

        if ($existing === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($existing): void {
            $blueprint->dropColumn($existing);
        });
    }

    /**
     * Tabela do model de usuário do projeto (nem sempre é "users").
     */
    private function usersTable(): string
    {
        $model = config('auth.providers.users.model');

        if (is_string($model) && class_exists($model)) {
            return (new $model())->getTable();
        }

        return 'users';
    }
};
