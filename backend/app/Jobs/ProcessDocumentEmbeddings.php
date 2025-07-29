<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentEmbeddings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Document $document;

    /**
     * Create a new job instance.
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * Execute the job.
     */
    public function handle(DocumentService $documentService): void
    {
        try {
            Log::info("Starting embedding generation for document {$this->document->id}");
            
            $success = $documentService->generateEmbeddingsForDocument($this->document);
            
            if ($success) {
                Log::info("Successfully generated embeddings for document {$this->document->id}");
            } else {
                Log::error("Failed to generate embeddings for document {$this->document->id}");
            }
        } catch (\Exception $e) {
            Log::error("Error processing embeddings for document {$this->document->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job failed for document {$this->document->id}: " . $exception->getMessage());
    }
}
