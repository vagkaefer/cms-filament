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
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        if (Schema::connection($connection)->hasTable($table)) {
            return;
        }

        $model = config('auth.providers.users.model');
        $userKeyIsUuid = is_string($model)
            && class_exists($model)
            && (new $model())->getKeyType() === 'string';

        Schema::connection($connection)->create($table, function (Blueprint $table) use ($userKeyIsUuid) {
            $morphPrefix = config('audit.user.morph_prefix', 'user');

            $table->bigIncrements('id');
            $table->string($morphPrefix . '_type')->nullable();

            if ($userKeyIsUuid) {
                $table->uuid($morphPrefix . '_id')->nullable();
            } else {
                $table->unsignedBigInteger($morphPrefix . '_id')->nullable();
            }

            $table->string('event');

            // `auditable_id` é string: a tabela guarda o histórico de models do
            // projeto (id inteiro) e do package, e uma coluna inteira recusaria
            // chaves UUID de qualquer model auditado. 36 caracteres cobrem os
            // dois formatos.
            $table->string('auditable_type');
            $table->string('auditable_id', 36);

            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();

            $table->index([$morphPrefix . '_id', $morphPrefix . '_type']);
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->dropIfExists($table);
    }
};
