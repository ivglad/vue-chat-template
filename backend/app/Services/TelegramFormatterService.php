<?php

namespace App\Services;

class TelegramFormatterService
{
    /**
     * Экранирует специальные символы Markdown для Telegram
     */
    public static function escapeMarkdown(string $text): string
    {
        // Список символов, которые нужно экранировать в Telegram Markdown
        $specialChars = ['.', '!'];
        
        foreach ($specialChars as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }
        
        return $text;
    }

    /**
     * Форматирует текст для безопасной отправки в Telegram
     */
    public static function formatForTelegram(string $text, bool $useMarkdown = false): array
    {
        if (!$useMarkdown) {
            // Конвертируем markdown в обычный текст для Telegram
            $plainText = self::convertMarkdownToPlainText($text);
            return [
                'text' => $plainText,
                'parse_mode' => null
            ];
        }

        // Пытаемся использовать экранированный markdown
        $escapedText = self::escapeMarkdown($text);
        
        return [
            'text' => $escapedText,
            'parse_mode' => 'MarkdownV2'
        ];
    }

    /**
     * Конвертирует markdown в читаемый обычный текст
     */
    public static function convertMarkdownToPlainText(string $text): string
    {
        // Заголовки
        $text = preg_replace('/^#{1,6}\s+(.+)$/m', '📌 $1', $text);
        
        // Жирный текст
        $text = preg_replace('/\*\*(.*?)\*\*/', '🔸 $1', $text);
        $text = preg_replace('/__(.*?)__/', '🔸 $1', $text);
        
        // Курсив
        $text = preg_replace('/\*(.*?)\*/', '$1', $text);
        $text = preg_replace('/_(.*?)_/', '$1', $text);
        
        // Код
        $text = preg_replace('/`([^`]+)`/', '💻 $1', $text);
        $text = preg_replace('/```[\s\S]*?```/', '💻 [Блок кода]', $text);
        
        // Ссылки
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '🔗 $1', $text);
        
        // Списки
        $text = preg_replace('/^[\s]*[-\*\+]\s+(.+)$/m', '• $1', $text);
        $text = preg_replace('/^[\s]*\d+\.\s+(.+)$/m', '🔢 $1', $text);
        
        // Цитаты
        $text = preg_replace('/^>\s+(.+)$/m', '💬 $1', $text);
        
        // Горизонтальные линии
        $text = preg_replace('/^[-\*_]{3,}$/m', '━━━━━━━━━━', $text);
        
        // Убираем лишние переносы строк
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }

    /**
     * Разбивает длинный текст на части для отправки
     */
    public static function splitLongMessage(string $text, int $maxLength = 4000): array
    {
        if (mb_strlen($text) <= $maxLength) {
            return [$text];
        }

        $parts = [];
        $currentPart = '';
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($sentences as $sentence) {
            if (mb_strlen($currentPart . $sentence) <= $maxLength) {
                $currentPart .= ($currentPart ? ' ' : '') . $sentence;
            } else {
                if ($currentPart) {
                    $parts[] = trim($currentPart);
                    $currentPart = $sentence;
                } else {
                    // Если одно предложение слишком длинное, разбиваем его принудительно
                    $chunks = str_split($sentence, $maxLength);
                    foreach ($chunks as $chunk) {
                        $parts[] = $chunk;
                    }
                }
            }
        }

        if ($currentPart) {
            $parts[] = trim($currentPart);
        }

        return $parts;
    }
} 