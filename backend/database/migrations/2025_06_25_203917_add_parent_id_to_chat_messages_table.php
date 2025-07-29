<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            // Добавляем поле для связи ответа бота с сообщением пользователя
            $table->unsignedBigInteger('parent_id')->nullable()->after('user_id');
            
            // Добавляем внешний ключ
            $table->foreign('parent_id')->references('id')->on('chat_messages')->onDelete('cascade');
            
            // Добавляем индексы для быстрого поиска
            $table->index(['user_id', 'parent_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            // Удаляем внешний ключ и индексы
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['user_id', 'parent_id']);
            $table->dropIndex(['user_id', 'created_at']);
            
            // Удаляем поле
            $table->dropColumn('parent_id');
        });
    }
};
