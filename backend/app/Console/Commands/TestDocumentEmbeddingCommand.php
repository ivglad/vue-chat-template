<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\DocumentService;

class TestDocumentEmbeddingCommand extends Command
{
    protected $signature = 'test:document-embedding {--document-id=} {--create-test}';
    protected $description = 'Тестирование генерации эмбеддингов для документа';

    public function handle()
    {
        $documentId = $this->option('document-id');
        $createTest = $this->option('create-test');

        if ($createTest) {
            $document = $this->createTestDocument();
            $this->info("Создан тестовый документ с ID: {$document->id}");
        } elseif ($documentId) {
            $document = Document::find($documentId);
            if (!$document) {
                $this->error("Документ с ID {$documentId} не найден");
                return;
            }
        } else {
            $this->error("Укажите --document-id=ID или --create-test");
            return;
        }

        $this->info("Тестирование генерации эмбеддингов для документа ID: {$document->id}");
        $this->info("Название: {$document->title}");
        $this->info("Метод разделения: " . config('app.chunking_method', env('CHUNKING_METHOD', 'adaptive')));
        $this->line("");

        $documentService = app(DocumentService::class);

        // Показываем информацию о чанках
        if ($document->content) {
            $chunks = $documentService->getChunksByMethod($document->content);
            $this->info("Количество чанков: " . count($chunks));
            $this->info("Средняя длина чанка: " . (count($chunks) > 0 ? array_sum(array_map('mb_strlen', $chunks)) / count($chunks) : 0));
            $this->line("");

            foreach ($chunks as $i => $chunk) {
                $this->info("Чанк " . ($i + 1) . " (длина: " . mb_strlen($chunk) . "):");
                $this->line(mb_substr($chunk, 0, 100) . (mb_strlen($chunk) > 100 ? '...' : ''));
                $this->line("");
            }
        }

        // Запускаем генерацию эмбеддингов
        $this->info("Запуск генерации эмбеддингов...");
        $result = $documentService->generateEmbeddingsForDocument($document);

        if ($result) {
            $this->info("✅ Генерация эмбеддингов запущена успешно");
            $this->info("Статус документа: " . $document->fresh()->processing_status);
            $this->line("");
            $this->info("Для проверки завершения выполните:");
            $this->line("php artisan queue:work --once");
        } else {
            $this->error("❌ Ошибка при запуске генерации эмбеддингов");
        }
    }

    private function createTestDocument(): Document
    {
        $testContent = file_get_contents('test_employees.txt');

        return Document::create([
            'title' => 'Тестовый документ - Список сотрудников',
            'google_docs_url' => 'https://docs.google.com/test-employees',
            'content' => $testContent,
            'user_id' => 1, // Предполагаем, что есть пользователь с ID 1
            'processing_status' => 'idle',
            'embeddings_generated' => false
        ]);
    }
}