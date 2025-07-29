<?php

namespace App\Services;

class MarkdownFormatterService
{
    /**
     * Конвертирует markdown в HTML для веб-интерфейса
     */
    public static function convertMarkdownToHtml(string $text): string
    {
        // Экранируем HTML теги для безопасности
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        
        // Заголовки
        $text = preg_replace('/^### (.+)$/m', '<h3 class="text-lg font-semibold mt-4 mb-2">$1</h3>', $text);
        $text = preg_replace('/^## (.+)$/m', '<h2 class="text-xl font-semibold mt-4 mb-2">$1</h2>', $text);
        $text = preg_replace('/^# (.+)$/m', '<h1 class="text-2xl font-bold mt-4 mb-2">$1</h1>', $text);
        
        // Жирный текст
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong class="font-semibold">$1</strong>', $text);
        $text = preg_replace('/__(.*?)__/', '<strong class="font-semibold">$1</strong>', $text);
        
        // Курсив
        $text = preg_replace('/\*(.*?)\*/', '<em class="italic">$1</em>', $text);
        $text = preg_replace('/_(.*?)_/', '<em class="italic">$1</em>', $text);
        
        // Код (инлайн)
        $text = preg_replace('/`([^`]+)`/', '<code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-sm font-mono">$1</code>', $text);
        
        // Блоки кода
        $text = preg_replace_callback('/```(\w+)?\n?([\s\S]*?)```/', function($matches) {
            $language = !empty($matches[1]) ? $matches[1] : '';
            $code = trim($matches[2]);
            $langLabel = $language ? '<span class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">💻 ' . $language . '</span>' : '';
            return '<div class="mt-2 mb-2">' . $langLabel . '<pre class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg overflow-x-auto"><code class="text-sm font-mono">' . $code . '</code></pre></div>';
        }, $text);
        
        // Ссылки
        $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" class="text-blue-600 dark:text-blue-400 hover:underline" target="_blank" rel="noopener">$1</a>', $text);
        
        // Неупорядоченные списки
        $text = preg_replace('/^[\s]*[-\*\+]\s+(.+)$/m', '<li class="ml-4">• $1</li>', $text);
        
        // Упорядоченные списки
        $text = preg_replace('/^[\s]*(\d+)\.\s+(.+)$/m', '<li class="ml-4">$1. $2</li>', $text);
        
        // Оборачиваем списки в ul/ol теги
        $text = preg_replace('/(<li[^>]*>.*?<\/li>(?:\s*<li[^>]*>.*?<\/li>)*)/s', '<ul class="space-y-1 mt-2 mb-2">$1</ul>', $text);
        
        // Цитаты
        $text = preg_replace('/^>\s+(.+)$/m', '<blockquote class="border-l-4 border-gray-300 dark:border-gray-600 pl-4 italic text-gray-700 dark:text-gray-300 mt-2 mb-2">$1</blockquote>', $text);
        
        // Горизонтальные линии
        $text = preg_replace('/^[-\*_]{3,}$/m', '<hr class="border-gray-300 dark:border-gray-600 my-4">', $text);
        
        // Переносы строк
        $text = preg_replace('/\n\n/', '</p><p class="mb-2">', $text);
        $text = preg_replace('/\n/', '<br>', $text);
        
        // Оборачиваем в параграфы
        if (!empty(trim($text))) {
            $text = '<p class="mb-2">' . $text . '</p>';
        }
        
        // Убираем пустые параграфы
        $text = preg_replace('/<p[^>]*><\/p>/', '', $text);
        
        return $text;
    }
    
    /**
     * Проверяет, содержит ли текст markdown разметку
     */
    public static function hasMarkdown(string $text): bool
    {
        $markdownPatterns = [
            '/^#{1,6}\s+/',           // Заголовки
            '/\*\*.*?\*\*/',          // Жирный текст
            '/__.*?__/',              // Жирный текст
            '/\*.*?\*/',              // Курсив
            '/_.*?_/',                // Курсив
            '/`.*?`/',                // Код
            '/```[\s\S]*?```/',       // Блоки кода
            '/\[.*?\]\(.*?\)/',       // Ссылки
            '/^[\s]*[-\*\+]\s+/m',    // Списки
            '/^[\s]*\d+\.\s+/m',      // Нумерованные списки
            '/^>\s+/m',               // Цитаты
            '/^[-\*_]{3,}$/m',        // Горизонтальные линии
        ];
        
        foreach ($markdownPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        
        return false;
    }
}