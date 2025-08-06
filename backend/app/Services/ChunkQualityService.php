<?php

namespace App\Services;

class ChunkQualityService
{
    /**
     * Оценка качества чанка
     */
    public function evaluateChunkQuality(string $chunk): array
    {
        return [
            'completeness_score' => $this->calculateCompletenessScore($chunk),
            'semantic_coherence' => $this->calculateSemanticCoherence($chunk),
            'information_density' => $this->calculateInformationDensity($chunk),
            'boundary_quality' => $this->calculateBoundaryQuality($chunk),
            'overall_score' => 0 // Будет рассчитан ниже
        ];
    }

    /**
     * Оценка полноты чанка (завершенность предложений)
     */
    private function calculateCompletenessScore(string $chunk): float
    {
        $chunk = trim($chunk);
        
        // Проверяем, заканчивается ли чанк знаком препинания
        $endsWithPunctuation = preg_match('/[.!?]$/', $chunk);
        
        // Проверяем, начинается ли с заглавной буквы
        $startsWithCapital = preg_match('/^[А-ЯA-Z]/', $chunk);
        
        // Подсчитываем количество полных предложений
        $sentences = preg_split('/[.!?]+/', $chunk);
        $completeSentences = count(array_filter($sentences, fn($s) => mb_strlen(trim($s)) > 10));
        
        $score = 0;
        $score += $endsWithPunctuation ? 0.4 : 0;
        $score += $startsWithCapital ? 0.3 : 0;
        $score += min($completeSentences / 3, 0.3); // Максимум 0.3 за количество предложений
        
        return $score;
    }

    /**
     * Оценка семантической связности
     */
    private function calculateSemanticCoherence(string $chunk): float
    {
        $sentences = preg_split('/[.!?]+/', $chunk);
        $sentences = array_filter($sentences, fn($s) => mb_strlen(trim($s)) > 10);
        
        // Переиндексируем массив для безопасного доступа
        $sentences = array_values($sentences);
        
        if (count($sentences) < 2) {
            return 0.5; // Нейтральная оценка для одного предложения
        }
        
        $coherenceScore = 0;
        $totalPairs = 0;
        
        // Анализируем связность между соседними предложениями
        for ($i = 0; $i < count($sentences) - 1; $i++) {
            if (isset($sentences[$i]) && isset($sentences[$i + 1])) {
                $coherenceScore += $this->calculateSentenceCoherence($sentences[$i], $sentences[$i + 1]);
                $totalPairs++;
            }
        }
        
        return $totalPairs > 0 ? $coherenceScore / $totalPairs : 0;
    }

    /**
     * Оценка связности между двумя предложениями
     */
    private function calculateSentenceCoherence(string $sentence1, string $sentence2): float
    {
        $words1 = $this->extractMeaningfulWords($sentence1);
        $words2 = $this->extractMeaningfulWords($sentence2);
        
        if (empty($words1) || empty($words2)) {
            return 0;
        }
        
        // Подсчитываем общие слова
        $commonWords = array_intersect($words1, $words2);
        $similarity = count($commonWords) / max(count($words1), count($words2));
        
        // Проверяем наличие связующих слов
        $connectiveWords = ['также', 'кроме того', 'однако', 'поэтому', 'таким образом', 'в результате'];
        $hasConnectives = false;
        foreach ($connectiveWords as $connective) {
            if (mb_strpos(mb_strtolower($sentence2), $connective) !== false) {
                $hasConnectives = true;
                break;
            }
        }
        
        return $similarity + ($hasConnectives ? 0.2 : 0);
    }

    /**
     * Извлечение значимых слов
     */
    private function extractMeaningfulWords(string $sentence): array
    {
        $words = preg_split('/\s+/', mb_strtolower($sentence));
        $stopWords = ['и', 'в', 'на', 'с', 'по', 'для', 'от', 'до', 'при', 'что', 'как', 'это', 'то', 'не', 'а', 'но', 'или', 'же', 'бы', 'ли'];
        
        return array_filter($words, function($word) use ($stopWords) {
            return mb_strlen($word) > 2 && !in_array($word, $stopWords) && preg_match('/^[а-яё]+$/u', $word);
        });
    }

    /**
     * Оценка информационной плотности
     */
    private function calculateInformationDensity(string $chunk): float
    {
        $meaningfulWords = $this->extractMeaningfulWords($chunk);
        $totalWords = count(preg_split('/\s+/', $chunk));
        
        if ($totalWords == 0) {
            return 0;
        }
        
        $density = count($meaningfulWords) / $totalWords;
        
        // Проверяем наличие чисел, дат, имен собственных
        $hasNumbers = preg_match('/\d+/', $chunk);
        $hasProperNouns = preg_match('/[А-ЯA-Z][а-яёa-z]+/', $chunk);
        
        $bonus = 0;
        $bonus += $hasNumbers ? 0.1 : 0;
        $bonus += $hasProperNouns ? 0.1 : 0;
        
        return min($density + $bonus, 1.0);
    }

    /**
     * Оценка качества границ чанка
     */
    private function calculateBoundaryQuality(string $chunk): float
    {
        $score = 0;
        
        // Проверяем, не обрывается ли чанк посередине слова
        $words = preg_split('/\s+/', trim($chunk));
        $lastWord = end($words);
        $firstWord = reset($words);
        
        // Последнее слово должно быть полным
        if (preg_match('/[а-яёa-z]$/ui', $lastWord)) {
            $score += 0.3;
        }
        
        // Первое слово должно начинаться с заглавной буквы или быть продолжением
        if (preg_match('/^[А-ЯA-Z]/', $firstWord) || mb_strpos($chunk, '[Контекст:') === 0) {
            $score += 0.3;
        }
        
        // Проверяем баланс скобок и кавычек
        $openBrackets = substr_count($chunk, '(') + substr_count($chunk, '[') + substr_count($chunk, '{');
        $closeBrackets = substr_count($chunk, ')') + substr_count($chunk, ']') + substr_count($chunk, '}');
        $openQuotes = substr_count($chunk, '"') + substr_count($chunk, '«');
        $closeQuotes = substr_count($chunk, '"') + substr_count($chunk, '»');
        
        if ($openBrackets == $closeBrackets && $openQuotes == $closeQuotes) {
            $score += 0.4;
        }
        
        return $score;
    }

    /**
     * Рекомендации по улучшению чанка
     */
    public function getImprovementSuggestions(string $chunk, array $qualityScores): array
    {
        $suggestions = [];
        
        if ($qualityScores['completeness_score'] < 0.5) {
            $suggestions[] = 'Чанк содержит неполные предложения. Рекомендуется расширить границы.';
        }
        
        if ($qualityScores['semantic_coherence'] < 0.3) {
            $suggestions[] = 'Низкая семантическая связность. Возможно, чанк содержит несвязанные темы.';
        }
        
        if ($qualityScores['information_density'] < 0.2) {
            $suggestions[] = 'Низкая информационная плотность. Чанк содержит много служебных слов.';
        }
        
        if ($qualityScores['boundary_quality'] < 0.5) {
            $suggestions[] = 'Проблемы с границами чанка. Возможны обрывы слов или несбалансированные скобки.';
        }
        
        return $suggestions;
    }

    /**
     * Автоматическое улучшение чанка
     */
    public function improveChunk(string $chunk, string $fullText): string
    {
        $improved = $chunk;
        
        // Попытка расширить чанк до полного предложения
        if (!preg_match('/[.!?]$/', trim($chunk))) {
            $chunkEnd = mb_strpos($fullText, $chunk) + mb_strlen($chunk);
            $nextPunctuation = $this->findNextPunctuation($fullText, $chunkEnd);
            
            if ($nextPunctuation !== false && $nextPunctuation - $chunkEnd < 100) {
                $improved = mb_substr($fullText, mb_strpos($fullText, $chunk), $nextPunctuation - mb_strpos($fullText, $chunk) + 1);
            }
        }
        
        return trim($improved);
    }

    /**
     * Поиск следующего знака препинания
     */
    private function findNextPunctuation(string $text, int $startPos): int|false
    {
        $punctuation = ['.', '!', '?'];
        $nearestPos = false;
        
        foreach ($punctuation as $punct) {
            $pos = mb_strpos($text, $punct, $startPos);
            if ($pos !== false && ($nearestPos === false || $pos < $nearestPos)) {
                $nearestPos = $pos;
            }
        }
        
        return $nearestPos;
    }
}