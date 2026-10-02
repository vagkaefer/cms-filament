<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Passkeys (WebAuthn) do spatie/laravel-passkeys.
     *
     * Equivalente ao stub do spatie, com duas diferenças: não depende do model
     * de usuário já declarar `HasPasskeys` (o stub estoura se não declarar) e
     * acompanha o tipo do id de `users`, inteiro ou uuid.
     *
     * A chave primária continua inteira mesmo com usuários uuid: o model
     * `Passkey` do spatie não gera uuid, e um `uuid('id')` sem default faz o
     * cadastro da passkey falhar.
     */
    public function up(): void
    {
        if (Schema::hasTable('passkeys')) {
            return;
        }

        $usersTable = $this->usersTable();
        $uuidUsers = $this->usersHaveUuid();

        Schema::create('passkeys', function (Blueprint $table) use ($usersTable, $uuidUsers): void {
            $table->id();

            $authenticatable = $uuidUsers
                ? $table->foreignUuid('authenticatable_id')
                : $table->foreignId('authenticatable_id');

            $authenticatable
                ->constrained(table: $usersTable, indexName: 'passkeys_authenticatable_fk')
                ->cascadeOnDelete();

            $table->text('name');
            $table->text('credential_id');
            $table->json('data');

            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passkeys');
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

    /**
     * Se o id de usuário é texto (uuid/ulid) em vez de inteiro.
     *
     * Vem do model (`HasUuids`/`HasUlids` deixam a chave como string), e não do
     * banco: assim a migration também roda com `migrate --pretend`, em que as
     * consultas de schema não devolvem nada.
     */
    private function usersHaveUuid(): bool
    {
        $model = config('auth.providers.users.model');

        if (! is_string($model) || ! class_exists($model)) {
            return false;
        }

        return (new $model())->getKeyType() === 'string';
    }
};
