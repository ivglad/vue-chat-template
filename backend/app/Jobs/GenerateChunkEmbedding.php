<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentEmbedding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\YandexGptService;
use Pgvector\Laravel\Vector;
use Log;

class GenerateChunkEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $documentId,
        public int $chunkIndex,
        public string $chunkText
    ) {}

    public function handle(YandexGptService $yandexGptService): void
    {
        $embedding = $yandexGptService->generateEmbeddings($this->chunkText);

        if ($embedding) {
            DocumentEmbedding::create([
                'document_id' => $this->documentId,
                'chunk_text' => $this->chunkText,
                'chunk_index' => $this->chunkIndex,
                'embedding' => $embedding,
                'embedding_vector' => new Vector($embedding),
            ]);

            // Проверяем, завершена ли обработка всех чанков
            $this->checkIfProcessingComplete();
        } else {
            Log::warning("Failed to generate embedding for document {$this->documentId}, chunk {$this->chunkIndex}");
            
            // Помечаем документ как failed при ошибке
            Document::where('id', $this->documentId)
                ->update(['processing_status' => 'failed']);
        }
    }

    private function checkIfProcessingComplete(): void
    {
        $document = Document::find($this->documentId);
        if (!$document || $document->processing_status !== 'processing') {
            return;
        }

        // Получаем количество обработанных чанков
        $processedChunks = DocumentEmbedding::where('document_id', $this->documentId)->count();
        
        // Получаем ожидаемое количество чанков из содержимого документа
        $documentService = app(\App\Services\DocumentService::class);
        $expectedChunks = count($documentService->splitTextIntoChunks($document->content));
        
        // Если все чанки обработаны, помечаем как завершенный
        if ($processedChunks >= $expectedChunks) {
            $document->update([
                'processing_status' => 'completed',
                'embeddings_generated' => true
            ]);
        }
    }
}
