<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tableNames), Exception::class, 'Erro: config/permission.php não carregado. Rode [php artisan config:clear] e tente de novo.');

        // Um projeto que já tenha as tabelas do Spatie (instalação anterior)
        // não deve quebrar ao instalar o package.
        if (Schema::hasTable($tableNames['roles'])) {
            return;
        }

        $morphKey = $this->morphKeyResolver($columnNames['model_morph_key']);

        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $morphKey) {
            $table->unsignedBigInteger($pivotPermission);

            $table->string('model_type');
            $morphKey($table);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->primary(
                [$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                'model_has_permissions_permission_model_type_primary'
            );
        });

        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $morphKey) {
            $table->unsignedBigInteger($pivotRole);

            $table->string('model_type');
            $morphKey($table);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary(
                [$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                'model_has_roles_role_model_type_primary'
            );
        });

        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        throw_if(empty($tableNames), Exception::class, 'Erro: config/permission.php não encontrado. Publique a configuração do package antes de reverter, ou remova as tabelas manualmente.');

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }

    /**
     * Devolve um callback que cria a coluna morph com o mesmo tipo da chave do
     * model de usuário do projeto.
     *
     * O package não presume id inteiro: um app com usuários em UUID precisa da
     * coluna como uuid, senão o vínculo usuário-cargo nem chega a ser gravado.
     *
     * @return callable(Blueprint): void
     */
    private function morphKeyResolver(string $column): callable
    {
        $model = config('auth.providers.users.model');
        $isUuid = is_string($model)
            && class_exists($model)
            && (new $model())->getKeyType() === 'string';

        return static function (Blueprint $table) use ($column, $isUuid): void {
            if ($isUuid) {
                $table->uuid($column);

                return;
            }

            $table->unsignedBigInteger($column);
        };
    }
};
