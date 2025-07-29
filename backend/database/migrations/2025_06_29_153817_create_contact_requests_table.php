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
        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->comment('Номер телефона');
            $table->text('comment')->nullable()->comment('Комментарий к заявке');
            $table->string('ip_address')->nullable()->comment('IP адрес отправителя');
            $table->string('user_agent')->nullable()->comment('User Agent');
            $table->boolean('is_sent_to_telegram')->default(false)->comment('Отправлено ли в Telegram');
            $table->timestamp('sent_to_telegram_at')->nullable()->comment('Время отправки в Telegram');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_requests');
    }
};
