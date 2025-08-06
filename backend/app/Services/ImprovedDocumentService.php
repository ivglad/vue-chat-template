<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentEmbedding;
use Illuminate\Support\Facades\Log;
use Pgvector\Laravel\Vector;

class ImprovedDocumentService extends DocumentService
{
    /**
     * Улучшенное разделение текста на чанки с учетом семантических границ
     */
    public function splitTextIntoChunks(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        // 1. Предварительная обработка текста
        $text = $this->preprocessText($text);
        
        // 2. Разделение на параграфы и структурные элементы
        $structuralChunks = $this->splitByStructure($text);
        
        // 3. Дальнейшее разделение больших структурных элементов
        $finalChunks = [];
        foreach ($structuralChunks as $chunk) {
            if (mb_strlen($chunk) <= $chunkSize) {
                $finalChunks[] = $chunk;
            } else {
                $finalChunks = array_merge($finalChunks, 
                    $this->splitLargeChunk($chunk, $chunkSize, $overlap));
            }
        }
        
        // 4. Добавление контекстного перекрытия
        $finalChunks = $this->addContextualOverlap($finalChunks, $overlap);
        
        // 5. Фильтрация и очистка
        return $this->filterAndCleanChunks($finalChunks);
    }

    /**
     * Предварительная обработка текста
     */
    private function preprocessText(string $text): string
    {
        // Нормализация пробелов и переносов строк
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        
        // Исправление распространенных проблем с пунктуацией
        $text = preg_replace('/([.!?])\s*([А-ЯA-Z])/', '$1 $2', $text);
        
        return trim($text);
    }

    /**
     * Разделение по структурным элементам
     */
    private function splitByStructure(string $text): array
    {
        $chunks = [];
        
        // Разделение по двойным переносам строк (параграфы)
        $paragraphs = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (mb_strlen($paragraph) > 50) {
                $chunks[] = $paragraph;
            }
        }
        
        // Если параграфов нет, разделяем по предложениям
        if (empty($chunks)) {
            $chunks = $this->splitBySentences($text);
        }
        
        return $chunks;
    }

    /**
     * Разделение по предложениям
     */
    private function splitBySentences(string $text): array
    {
        // Проверяем, является ли текст структурированным списком
        if ($this->isStructuredList($text)) {
            return $this->splitStructuredList($text);
        }
        
        // Улучшенное регулярное выражение для разделения по предложениям
        $sentences = preg_split('/(?<=[.!?])\s+(?=[А-ЯA-Z])/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $filtered = array_filter($sentences, fn($sentence) => mb_strlen(trim($sentence)) > 20);
        
        // Переиндексируем массив для безопасного доступа
        return array_values($filtered);
    }

    /**
     * Проверка, является ли текст структурированным списком
     */
    private function isStructuredList(string $text): bool
    {
        // Ищем паттерны, характерные для списков сотрудников, товаров и т.д.
        $patterns = [
            '/\w+\s+\w+\s+\w+,\s*Должность:/',  // ФИО, Должность:
            '/\w+:\s*[^,]+,\s*\w+:\s*[^,]+,/',   // Ключ: значение, Ключ: значение,
            '/!\s+\w+\s+\w+\s+\w+,/',            // ! ФИО,
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Разделение структурированного списка
     */
    private function splitStructuredList(string $text): array
    {
        // Разделяем по паттерну "! ФИО" или по двойным пробелам
        $items = preg_split('/!\s+(?=[А-ЯЁ][а-яё]+\s+[А-ЯЁ][а-яё]+\s+[А-ЯЁ][а-яё]+)/', $text);
        
        if (count($items) < 2) {
            // Пробуем разделить по двойным пробелам
            $items = preg_split('/\s{2,}/', $text);
        }
        
        $sentences = [];
        foreach ($items as $item) {
            $item = trim($item);
            if (mb_strlen($item) > 20) {
                $sentences[] = $item;
            }
        }
        
        return array_values($sentences);
    }

    /**
     * Разделение больших чанков с сохранением семантики
     */
    private function splitLargeChunk(string $chunk, int $chunkSize, int $overlap): array
    {
        $subChunks = [];
        $sentences = $this->splitBySentences($chunk);
        
        $currentChunk = '';
        $currentLength = 0;
        
        foreach ($sentences as $sentence) {
            $sentenceLength = mb_strlen($sentence);
            
            // Если добавление предложения превысит лимит
            if ($currentLength + $sentenceLength > $chunkSize && !empty($currentChunk)) {
                $subChunks[] = trim($currentChunk);
                
                // Начинаем новый чанк с перекрытием
                $overlapText = $this->getLastSentences($currentChunk, $overlap);
                $currentChunk = $overlapText . ' ' . $sentence;
                $currentLength = mb_strlen($currentChunk);
            } else {
                $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
                $currentLength += $sentenceLength + 1;
            }
        }
        
        if (!empty($currentChunk)) {
            $subChunks[] = trim($currentChunk);
        }
        
        return $subChunks;
    }

    /**
     * Получение последних предложений для перекрытия
     */
    private function getLastSentences(string $text, int $maxLength): string
    {
        $sentences = $this->splitBySentences($text);
        if (empty($sentences)) {
            return '';
        }
        
        // Переиндексируем массив для безопасного доступа
        $sentences = array_values($sentences);
        $result = '';
        $length = 0;
        
        // Идем с конца и добавляем предложения, пока не превысим лимит
        for ($i = count($sentences) - 1; $i >= 0; $i--) {
            if (!isset($sentences[$i])) {
                continue;
            }
            
            $sentence = $sentences[$i];
            $sentenceLength = mb_strlen($sentence);
            
            if ($length + $sentenceLength <= $maxLength) {
                $result = $sentence . ($result ? ' ' : '') . $result;
                $length += $sentenceLength + 1;
            } else {
                break;
            }
        }
        
        return $result;
    }

    /**
     * Добавление контекстного перекрытия между чанками
     */
    private function addContextualOverlap(array $chunks, int $overlap): array
    {
        if (count($chunks) <= 1) {
            return $chunks;
        }
        
        // Переиндексируем массив для безопасного доступа
        $chunks = array_values($chunks);
        $improvedChunks = [];
        
        for ($i = 0; $i < count($chunks); $i++) {
            $chunk = $chunks[$i];
            
            // Добавляем контекст из предыдущего чанка
            if ($i > 0 && isset($chunks[$i - 1])) {
                $prevContext = $this->getLastSentences($chunks[$i - 1], $overlap / 2);
                if (!empty($prevContext)) {
                    $chunk = "[Контекст: " . $prevContext . "] " . $chunk;
                }
            }
            
            // Добавляем контекст из следующего чанка
            if ($i < count($chunks) - 1 && isset($chunks[$i + 1])) {
                $nextContext = $this->getFirstSentences($chunks[$i + 1], $overlap / 2);
                if (!empty($nextContext)) {
                    $chunk = $chunk . " [Продолжение: " . $nextContext . "]";
                }
            }
            
            $improvedChunks[] = $chunk;
        }
        
        return $improvedChunks;
    }

    /**
     * Получение первых предложений
     */
    private function getFirstSentences(string $text, int $maxLength): string
    {
        $sentences = $this->splitBySentences($text);
        if (empty($sentences)) {
            return '';
        }
        
        $result = '';
        $length = 0;
        
        foreach ($sentences as $sentence) {
            if (empty($sentence)) {
                continue;
            }
            
            $sentenceLength = mb_strlen($sentence);
            
            if ($length + $sentenceLength <= $maxLength) {
                $result .= ($result ? ' ' : '') . $sentence;
                $length += $sentenceLength + 1;
            } else {
                break;
            }
        }
        
        return $result;
    }

    /**
     * Фильтрация и очистка чанков
     */
    private function filterAndCleanChunks(array $chunks): array
    {
        return array_filter(
            array_map('trim', $chunks),
            fn($chunk) => mb_strlen($chunk) > 50 && !empty(trim($chunk))
        );
    }

    /**
     * Альтернативный метод: семантическое разделение с использованием ключевых слов
     */
    public function semanticSplitTextIntoChunks(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        // 1. Извлекаем ключевые фразы и термины
        $keyPhrases = $this->extractKeyPhrases($text);
        
        // 2. Разделяем текст на предложения
        $sentences = $this->splitBySentences($text);
        
        // 3. Группируем предложения по семантической близости
        $semanticGroups = $this->groupSentencesBySemantic($sentences, $keyPhrases);
        
        // 4. Формируем чанки из семантических групп
        $chunks = [];
        $currentChunk = '';
        $currentLength = 0;
        
        foreach ($semanticGroups as $group) {
            $groupText = implode(' ', $group);
            $groupLength = mb_strlen($groupText);
            
            if ($currentLength + $groupLength > $chunkSize && !empty($currentChunk)) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $groupText;
                $currentLength = $groupLength;
            } else {
                $currentChunk .= ($currentChunk ? ' ' : '') . $groupText;
                $currentLength += $groupLength + 1;
            }
        }
        
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }
        
        return $this->filterAndCleanChunks($chunks);
    }

    /**
     * Извлечение ключевых фраз (упрощенная версия)
     */
    private function extractKeyPhrases(string $text): array
    {
        // Простое извлечение часто встречающихся слов и фраз
        $words = preg_split('/\s+/', mb_strtolower($text));
        $wordFreq = array_count_values($words);
        
        // Фильтруем стоп-слова и короткие слова
        $stopWords = ['и', 'в', 'на', 'с', 'по', 'для', 'от', 'до', 'при', 'что', 'как', 'это', 'то', 'не', 'а', 'но'];
        $keyWords = array_filter($wordFreq, function($word) use ($stopWords) {
            return mb_strlen($word) > 3 && !in_array($word, $stopWords);
        }, ARRAY_FILTER_USE_KEY);
        
        // Возвращаем топ ключевых слов
        arsort($keyWords);
        return array_keys(array_slice($keyWords, 0, 20));
    }

    /**
     * Группировка предложений по семантической близости
     */
    private function groupSentencesBySemantic(array $sentences, array $keyPhrases): array
    {
        $groups = [];
        $currentGroup = [];
        $currentKeywords = [];
        
        foreach ($sentences as $sentence) {
            $sentenceKeywords = $this->findKeywordsInSentence($sentence, $keyPhrases);
            
            // Если есть общие ключевые слова с текущей группой, добавляем к ней
            if (!empty($currentGroup) && !empty(array_intersect($currentKeywords, $sentenceKeywords))) {
                $currentGroup[] = $sentence;
                $currentKeywords = array_unique(array_merge($currentKeywords, $sentenceKeywords));
            } else {
                // Начинаем новую группу
                if (!empty($currentGroup)) {
                    $groups[] = $currentGroup;
                }
                $currentGroup = [$sentence];
                $currentKeywords = $sentenceKeywords;
            }
        }
        
        if (!empty($currentGroup)) {
            $groups[] = $currentGroup;
        }
        
        return $groups;
    }

    /**
     * Поиск ключевых слов в предложении
     */
    private function findKeywordsInSentence(string $sentence, array $keyPhrases): array
    {
        $found = [];
        $lowerSentence = mb_strtolower($sentence);
        
        foreach ($keyPhrases as $phrase) {
            if (mb_strpos($lowerSentence, $phrase) !== false) {
                $found[] = $phrase;
            }
        }
        
        return $found;
    }
}