<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentEmbedding;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Pgvector\Laravel\Vector;
use Pgvector\Laravel\Distance;

class SearchService
{
    private YandexGptService $yandexGptService;

    public function __construct(YandexGptService $yandexGptService)
    {
        $this->yandexGptService = $yandexGptService;

        // Log::channel('documents')->debug("SearchService instantiated.");
    }

    /**
     * Найти релевантные документы для пользователя
     */
    public function findRelevantDocuments(
        User $user, 
        string $question, 
        int $limit = 5, 
        array $selectedDocuments = []
    ): Collection {
        // Генерируем эмбеддинг для вопроса
        $questionEmbedding = $this->yandexGptService->generateEmbeddings($question);
        
        if (!$questionEmbedding) {
            Log::error('Failed to generate embeddings for search question', ['question' => $question]);
            return collect();
        }

        // Валидируем выбранные документы
        $selectedDocuments = $this->validateSelectedDocuments($user, $selectedDocuments);

        // Получаем релевантные чанки с помощью pgvector
        return $this->searchWithPgVector($user, $questionEmbedding, $limit, $selectedDocuments);
    }

    /**
     * Поиск с использованием pgvector для максимальной производительности
     */
    private function searchWithPgVector(
        User $user, 
        array $questionEmbedding, 
        int $limit, 
        array $selectedDocuments
    ): Collection {
        $questionVector = new Vector($questionEmbedding);

        // Строим запрос с учетом прав доступа пользователя
        $query = DocumentEmbedding::query()
            ->with('document')
            ->whereHas('document', function ($query) use ($user, $selectedDocuments) {
                // Фильтруем по доступным пользователю документам
                $query->where(function ($subQuery) use ($user) {
                    $subQuery->where('user_id', $user->id)
                        ->orWhereHas('roles', function ($roleQuery) use ($user) {
                            $roleQuery->whereIn('roles.id', $user->roles->pluck('id'));
                        });
                });
                
                // Если указаны конкретные документы, фильтруем по ним
                if (!empty($selectedDocuments)) {
                    $query->whereIn('id', $selectedDocuments);
                }
            })
            ->whereNotNull('embedding_vector');

        // Используем pgvector для быстрого поиска ближайших соседей
        $nearestChunks = $query
            ->nearestNeighbors('embedding_vector', $questionVector, Distance::Cosine)
            ->take($limit)
            ->get();

        // Преобразуем результаты в стандартный формат
        return $nearestChunks->map(function ($embedding) {

            // Log::channel('documents')->info("Finished searchWithPgVector.", [
            //     'embedding' => $embedding,
            //     'similarity' => 1 - $embedding->neighbor_distance,
            //     'document_id' => $embedding->document_id,
            //     'chunk_text' => $embedding->chunk_text,
            //     'document' => $embedding->document
            // ]);

            return (object) [
                'embedding' => $embedding,
                'similarity' => 1 - $embedding->neighbor_distance, // Переводим distance в similarity
                'document_id' => $embedding->document_id,
                'chunk_text' => $embedding->chunk_text,
                'document' => $embedding->document
            ];
        });
    }

    /**
     * Построить контекст из релевантных документов
     */
    public function buildContext(Collection $relevantDocuments): string
    {
        $contextParts = [];
        
        foreach ($relevantDocuments as $result) {
            $document = $result->document;
            $chunkText = $result->chunk_text;
            
            if ($document && $chunkText) {
                $contextParts[] = "Документ \"{$document->title}\":\n{$chunkText}";
            }
        }

        Log::channel('documents')->info("Finished buildContext.", [
            'contextParts' => implode("\n\n", $contextParts),
        ]);

        return implode("\n\n", $contextParts);
    }

    /**
     * Получить названия документов из результатов поиска
     */
    public function getDocumentTitles(Collection $relevantDocuments): array
    {
        return $relevantDocuments->map(function ($result) {
            return $result->document->title ?? 'Неизвестный документ';
        })->unique()->values()->toArray();
    }

    /**
     * Валидировать выбранные документы пользователя
     */
    private function validateSelectedDocuments(User $user, array $selectedDocuments): array
    {
        if (empty($selectedDocuments)) {
            return [];
        }

        // Получаем доступные документы пользователя
        $availableDocumentIds = Document::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('roles', function ($roleQuery) use ($user) {
                    $roleQuery->whereIn('roles.id', $user->roles->pluck('id'));
                });
        })
        ->where('embeddings_generated', true)
        ->pluck('id')
        ->toArray();

        // Фильтруем выбранные документы, оставляя только доступные
        $validDocuments = array_intersect($selectedDocuments, $availableDocumentIds);

        // Логируем попытки доступа к недоступным документам
        $invalidDocuments = array_diff($selectedDocuments, $availableDocumentIds);
        if (!empty($invalidDocuments)) {
            Log::warning('User tried to access unauthorized documents', [
                'user_id' => $user->id,
                'invalid_documents' => $invalidDocuments,
                'valid_documents' => $validDocuments
            ]);
        }

        return array_values($validDocuments);
    }

    /**
     * Логирование качества поиска
     */
    public function logSearchQuality(string $question, Collection $results, float $executionTime): void
    {
        $avgSimilarity = $results->avg('similarity');
        $minSimilarity = $results->min('similarity');
        $maxSimilarity = $results->max('similarity');
        
        Log::info('Search quality metrics', [
            'question_length' => mb_strlen($question),
            'results_count' => $results->count(),
            'avg_similarity' => round($avgSimilarity, 3),
            'min_similarity' => round($minSimilarity, 3),
            'max_similarity' => round($maxSimilarity, 3),
            'execution_time_ms' => round($executionTime * 1000, 2),
            'unique_documents' => $results->pluck('document_id')->unique()->count()
        ]);
    }
}