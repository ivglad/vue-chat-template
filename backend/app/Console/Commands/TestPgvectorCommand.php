<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DocumentEmbedding;
use App\Models\Document;
use Pgvector\Laravel\Vector;
use Pgvector\Laravel\Distance;

class TestPgvectorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:pgvector';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test pgvector functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing pgvector functionality...');

        try {
            // Создаем тестовые векторы (имитируем эмбеддинги размерности 1024)
            $testVectors = [
                array_fill(0, 1024, 0.1), // Вектор из единиц
                array_fill(0, 1024, 0.2), // Вектор из двоек
                array_fill(0, 1024, 0.5), // Вектор из пятерок
            ];

            // Добавляем случайные вариации
            for ($i = 0; $i < 3; $i++) {
                for ($j = 0; $j < 1024; $j++) {
                    $testVectors[$i][$j] += (rand(-10, 10) / 100); // Добавляем шум ±0.1
                }
            }

            $this->info('Creating test embeddings...');

            // Создаем тестовый документ если его нет
            $testDocument = Document::firstOrCreate([
                'title' => 'Test Document for pgvector',
            ], [
                'google_docs_url' => 'https://example.com/test-doc',
                'content' => 'This is a test document for testing pgvector functionality.',
                'embeddings_generated' => true,
            ]);

            // Очищаем старые тестовые данные
            DocumentEmbedding::where('document_id', $testDocument->id)->delete();

            // Создаем тестовые эмбеддинги
            foreach ($testVectors as $index => $vector) {
                DocumentEmbedding::create([
                    'document_id' => $testDocument->id,
                    'chunk_text' => "Test chunk $index",
                    'chunk_index' => $index,
                    'embedding' => $vector,
                    'embedding_vector' => new Vector($vector),
                ]);
                $this->info("Created test embedding $index");
            }

            // Тестируем поиск ближайших соседей
            $this->info('Testing nearest neighbor search...');
            
            // Создаем поисковый вектор похожий на первый тестовый вектор
            $searchVector = array_fill(0, 1024, 0.1);
            // Добавляем небольшой шум
            for ($j = 0; $j < 1024; $j++) {
                $searchVector[$j] += (rand(-5, 5) / 100);
            }

            $queryVector = new Vector($searchVector);

            // Ищем ближайших соседей
            $nearestChunks = DocumentEmbedding::query()
                ->where('document_id', $testDocument->id)
                ->whereNotNull('embedding_vector')
                ->nearestNeighbors('embedding_vector', $queryVector, Distance::Cosine)
                ->take(3)
                ->get();

            $this->info('Nearest neighbors found:');
            foreach ($nearestChunks as $chunk) {
                $similarity = 1 - $chunk->neighbor_distance;
                $this->info("- {$chunk->chunk_text}: similarity = " . round($similarity, 4));
            }

            // Тестируем также L2 расстояние
            $this->info('Testing with L2 distance...');
            $nearestChunksL2 = DocumentEmbedding::query()
                ->where('document_id', $testDocument->id)
                ->whereNotNull('embedding_vector')
                ->nearestNeighbors('embedding_vector', $queryVector, Distance::L2)
                ->take(3)
                ->get();

            $this->info('Nearest neighbors (L2 distance):');
            foreach ($nearestChunksL2 as $chunk) {
                $this->info("- {$chunk->chunk_text}: distance = " . round($chunk->neighbor_distance, 4));
            }

            $this->info('pgvector test completed successfully! ✅');

        } catch (\Exception $e) {
            $this->error('Error testing pgvector: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
