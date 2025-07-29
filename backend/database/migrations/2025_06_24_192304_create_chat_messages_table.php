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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message'); // Сообщение пользователя
            $table->longText('response')->nullable(); // Ответ от YandexGPT
            $table->enum('type', ['user', 'bot'])->default('user'); // Тип сообщения
            $table->boolean('is_processed')->default(false); // Обработано ли сообщение
            $table->json('context_documents')->nullable(); // ID документов, использованных как контекст
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
