<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Создаем индекс для поиска по telegram_data->id в PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX users_telegram_id_index ON users USING btree ((telegram_data->>\'id\'))');
        } else {
            // Для других баз данных создаем обычный индекс
            Schema::table('users', function (Blueprint $table) {
                $table->index(['telegram_data']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем индекс
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS users_telegram_id_index');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['telegram_data']);
            });
        }
    }
};
