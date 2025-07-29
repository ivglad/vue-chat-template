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
        Schema::table('documents', function (Blueprint $table) {
            $table->longText('content')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->boolean('embeddings_generated')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['content', 'file_path', 'file_type', 'embeddings_generated', 'user_id']);
        });
    }
};
