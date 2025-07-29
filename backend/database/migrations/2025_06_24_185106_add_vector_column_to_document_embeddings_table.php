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
        Schema::table('document_embeddings', function (Blueprint $table) {
            // Добавляем новую колонку vector с размерностью 1024 (размер эмбеддингов YandexGPT)
            $table->vector('embedding_vector', 256)->nullable();
        });

        // Копируем данные из JSON колонки в новую vector колонку
        DB::statement("
            UPDATE document_embeddings 
            SET embedding_vector = embedding::text::vector 
            WHERE embedding IS NOT NULL
        ");

        // Создаем индекс для быстрого поиска
        DB::statement('CREATE INDEX document_embeddings_embedding_vector_idx ON document_embeddings USING hnsw (embedding_vector vector_cosine_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_embeddings', function (Blueprint $table) {
            // Удаляем индекс
            DB::statement('DROP INDEX IF EXISTS document_embeddings_embedding_vector_idx');
            
            // Удаляем колонку
            $table->dropColumn('embedding_vector');
        });
    }
};
