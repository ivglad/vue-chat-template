<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;

class UpdateDocumentProcessingStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update processing status for existing documents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating document processing statuses...');

        // Обновляем документы с эмбеддингами как completed
        $completedCount = Document::where('embeddings_generated', true)
            ->whereNull('processing_status')
            ->update(['processing_status' => 'completed']);

        // Обновляем документы без эмбеддингов как idle
        $idleCount = Document::where('embeddings_generated', false)
            ->whereNull('processing_status')
            ->update(['processing_status' => 'idle']);

        $this->info("Updated {$completedCount} documents to 'completed' status");
        $this->info("Updated {$idleCount} documents to 'idle' status");
        $this->info('Done!');
    }
}
