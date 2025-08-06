<?php

namespace App\Services;

class AdaptiveChunkingService
{
    private ChunkQualityService $qualityService;
    
    public function __construct(ChunkQualityService $qualityService)
    {
        $this->qualityService = $qualityService;
    }

    /**
     * Адаптивное разделение текста с оптимизацией качества
     */
    public function adaptiveChunking(string $text, array $options = []): array
    {
        $options = array_merge([
            'target_chunk_size' => 1000,
            'min_chunk_size' => 200,
            'max_chunk_size' => 1500,
            'overlap_ratio' => 0.2,
            'quality_threshold' => 0.6,
            'max_iterations' => 3
        ], $options);

        $chunks = $this->initialChunking($text, $options);
        
        // Итеративное улучшение качества
        for ($iteration = 0; $iteration < $options['max_iterations']; $iteration++) {
            $improved = false;
            $newChunks = [];
            
            foreach ($chunks as $i => $chunk) {
                $quality = $this->qualityService->evaluateChunkQuality($chunk);
                $overallScore = $this->calculateOverallScore($quality);
                
                if ($overallScore < $options['quality_threshold']) {
                    $improvedChunk = $this->optimizeChunk($chunk, $chunks, $i, $text, $options);
                    if ($improvedChunk !== $chunk) {
                        $newChunks[] = $improvedChunk;
                        $improved = true;
                    } else {
                        $newChunks[] = $chunk;
                    }
                } else {
                    $newChunks[] = $chunk;
                }
            }
            
            $chunks = $newChunks;
            
            if (!$improved) {
                break; // Больше улучшений не найдено
            }
        }
        
        return $this->finalizeChunks($chunks, $options);
    }

    /**
     * Начальное разделение на чанки
     */
    private function initialChunking(string $text, array $options): array
    {
        // Используем улучшенный алгоритм разделения
        $improvedService = new ImprovedDocumentService(
            app(\App\Services\YandexGptService::class),
            app(\App\Services\GigaChatService::class),
            app(\App\Services\OpenRouterService::class)
        );
        
        return $improvedService->splitTextIntoChunks(
            $text, 
            $options['target_chunk_size'], 
            (int)($options['target_chunk_size'] * $options['overlap_ratio'])
        );
    }

    /**
     * Расчет общей оценки качества
     */
    private function calculateOverallScore(array $quality): float
    {
        $weights = [
            'completeness_score' => 0.3,
            'semantic_coherence' => 0.3,
            'information_density' => 0.2,
            'boundary_quality' => 0.2
        ];
        
        $score = 0;
        foreach ($weights as $metric => $weight) {
            $score += ($quality[$metric] ?? 0) * $weight;
        }
        
        return $score;
    }

    /**
     * Оптимизация отдельного чанка
     */
    private function optimizeChunk(string $chunk, array $allChunks, int $index, string $fullText, array $options): string
    {
        $quality = $this->qualityService->evaluateChunkQuality($chunk);
        
        // Стратегия 1: Расширение границ для улучшения полноты
        if ($quality['completeness_score'] < 0.5) {
            $expanded = $this->expandChunkBoundaries($chunk, $fullText, $options);
            if ($expanded !== $chunk) {
                return $expanded;
            }
        }
        
        // Стратегия 2: Объединение с соседними чанками при низкой плотности
        if ($quality['information_density'] < 0.3 && mb_strlen($chunk) < $options['target_chunk_size']) {
            $merged = $this->tryMergeWithNeighbors($chunk, $allChunks, $index, $options);
            if ($merged !== $chunk) {
                return $merged;
            }
        }
        
        // Стратегия 3: Разделение при низкой семантической связности
        if ($quality['semantic_coherence'] < 0.3 && mb_strlen($chunk) > $options['min_chunk_size'] * 2) {
            $split = $this->trySplitChunk($chunk, $options);
            if (count($split) > 1) {
                return $split[0]; // Возвращаем первую часть, вторая будет обработана отдельно
            }
        }
        
        return $chunk;
    }

    /**
     * Расширение границ чанка
     */
    private function expandChunkBoundaries(string $chunk, string $fullText, array $options): string
    {
        $chunkStart = mb_strpos($fullText, $chunk);
        if ($chunkStart === false) {
            return $chunk;
        }
        
        $chunkEnd = $chunkStart + mb_strlen($chunk);
        $maxExpansion = $options['max_chunk_size'] - mb_strlen($chunk);
        
        // Расширяем вперед до конца предложения
        $forwardExpansion = min($maxExpansion, 200);
        $expandedEnd = $chunkEnd;
        
        for ($i = $chunkEnd; $i < min($chunkEnd + $forwardExpansion, mb_strlen($fullText)); $i++) {
            $char = mb_substr($fullText, $i, 1);
            if (in_array($char, ['.', '!', '?'])) {
                $expandedEnd = $i + 1;
                break;
            }
        }
        
        if ($expandedEnd > $chunkEnd) {
            return mb_substr($fullText, $chunkStart, $expandedEnd - $chunkStart);
        }
        
        return $chunk;
    }

    /**
     * Попытка объединения с соседними чанками
     */
    private function tryMergeWithNeighbors(string $chunk, array $allChunks, int $index, array $options): string
    {
        // Переиндексируем массив для безопасного доступа
        $chunks = array_values($allChunks);
        
        // Пробуем объединить с предыдущим чанком
        if ($index > 0 && isset($chunks[$index - 1])) {
            $prevChunk = $chunks[$index - 1];
            $merged = $prevChunk . ' ' . $chunk;
            
            if (mb_strlen($merged) <= $options['max_chunk_size']) {
                $quality = $this->qualityService->evaluateChunkQuality($merged);
                if ($this->calculateOverallScore($quality) > 0.5) {
                    return $merged;
                }
            }
        }
        
        // Пробуем объединить со следующим чанком
        if ($index < count($chunks) - 1 && isset($chunks[$index + 1])) {
            $nextChunk = $chunks[$index + 1];
            $merged = $chunk . ' ' . $nextChunk;
            
            if (mb_strlen($merged) <= $options['max_chunk_size']) {
                $quality = $this->qualityService->evaluateChunkQuality($merged);
                if ($this->calculateOverallScore($quality) > 0.5) {
                    return $merged;
                }
            }
        }
        
        return $chunk;
    }

    /**
     * Попытка разделения чанка
     */
    private function trySplitChunk(string $chunk, array $options): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $chunk, -1, PREG_SPLIT_NO_EMPTY);
        
        if (count($sentences) < 2) {
            return [$chunk];
        }
        
        $midPoint = (int)(count($sentences) / 2);
        $firstHalf = implode(' ', array_slice($sentences, 0, $midPoint));
        $secondHalf = implode(' ', array_slice($sentences, $midPoint));
        
        // Проверяем, что обе части достаточно большие
        if (mb_strlen($firstHalf) >= $options['min_chunk_size'] && 
            mb_strlen($secondHalf) >= $options['min_chunk_size']) {
            return [$firstHalf, $secondHalf];
        }
        
        return [$chunk];
    }

    /**
     * Финализация чанков
     */
    private function finalizeChunks(array $chunks, array $options): array
    {
        $finalized = [];
        
        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            
            // Фильтруем слишком короткие чанки
            if (mb_strlen($chunk) >= $options['min_chunk_size']) {
                $finalized[] = $chunk;
            }
        }
        
        return $finalized;
    }

    /**
     * Анализ и отчет о качестве разделения
     */
    public function analyzeChunkingQuality(array $chunks): array
    {
        $totalChunks = count($chunks);
        $qualityScores = [];
        $totalScore = 0;
        
        foreach ($chunks as $i => $chunk) {
            $quality = $this->qualityService->evaluateChunkQuality($chunk);
            $overallScore = $this->calculateOverallScore($quality);
            
            $qualityScores[] = [
                'chunk_index' => $i,
                'chunk_length' => mb_strlen($chunk),
                'quality_metrics' => $quality,
                'overall_score' => $overallScore,
                'suggestions' => $this->qualityService->getImprovementSuggestions($chunk, $quality)
            ];
            
            $totalScore += $overallScore;
        }
        
        return [
            'total_chunks' => $totalChunks,
            'average_quality' => $totalChunks > 0 ? $totalScore / $totalChunks : 0,
            'chunk_details' => $qualityScores,
            'recommendations' => $this->generateGlobalRecommendations($qualityScores)
        ];
    }

    /**
     * Генерация глобальных рекомендаций
     */
    private function generateGlobalRecommendations(array $qualityScores): array
    {
        $recommendations = [];
        $lowQualityCount = count(array_filter($qualityScores, fn($score) => $score['overall_score'] < 0.5));
        
        if ($lowQualityCount > count($qualityScores) * 0.3) {
            $recommendations[] = 'Более 30% чанков имеют низкое качество. Рекомендуется пересмотреть параметры разделения.';
        }
        
        $avgLength = array_sum(array_column($qualityScores, 'chunk_length')) / count($qualityScores);
        if ($avgLength < 300) {
            $recommendations[] = 'Средняя длина чанков слишком мала. Увеличьте target_chunk_size.';
        } elseif ($avgLength > 1200) {
            $recommendations[] = 'Средняя длина чанков слишком велика. Уменьшите target_chunk_size.';
        }
        
        return $recommendations;
    }
}