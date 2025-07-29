<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DocumentEmbedding;
use Pgvector\Laravel\Vector;

class MigrateEmbeddingsToVectorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:embeddings-to-vector {--force : Force migration without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing JSON embeddings to pgvector format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of embeddings to pgvector format...');

        // Подсчитываем эмбеддинги которые нужно мигрировать
        $totalEmbeddings = DocumentEmbedding::whereNotNull('embedding')
            ->whereNull('embedding_vector')
            ->count();

        if ($totalEmbeddings === 0) {
            $this->info('No embeddings to migrate. All embeddings are already in vector format.');
            return 0;
        }

        $this->info("Found {$totalEmbeddings} embeddings to migrate.");

        if (!$this->option('force') && !$this->confirm('Do you want to proceed with the migration?')) {
            $this->info('Migration cancelled.');
            return 0;
        }

        $bar = $this->output->createProgressBar($totalEmbeddings);
        $bar->start();

        $successCount = 0;
        $errorCount = 0;

        DocumentEmbedding::whereNotNull('embedding')
            ->whereNull('embedding_vector')
            ->chunk(100, function ($embeddings) use ($bar, &$successCount, &$errorCount) {
                foreach ($embeddings as $embedding) {
                    try {
                        if (is_array($embedding->embedding) && count($embedding->embedding) > 0) {
                            $embedding->embedding_vector = new Vector($embedding->embedding);
                            $embedding->save();
                            $successCount++;
                        } else {
                            $this->newLine();
                            $this->warn("Skipping embedding {$embedding->id}: invalid embedding data");
                            $errorCount++;
                        }
                    } catch (\Exception $e) {
                        $this->newLine();
                        $this->error("Error migrating embedding {$embedding->id}: " . $e->getMessage());
                        $errorCount++;
                    }
                    
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();

        $this->info("Migration completed!");
        $this->info("Successfully migrated: {$successCount} embeddings");
        
        if ($errorCount > 0) {
            $this->warn("Errors encountered: {$errorCount} embeddings");
        }

        // Проверяем результат
        $remainingEmbeddings = DocumentEmbedding::whereNotNull('embedding')
            ->whereNull('embedding_vector')
            ->count();

        if ($remainingEmbeddings > 0) {
            $this->warn("Warning: {$remainingEmbeddings} embeddings still need to be migrated.");
        } else {
            $this->info('✅ All embeddings have been successfully migrated to pgvector format!');
        }

        return 0;
    }
}
