<?php

namespace App\Console\Commands;

use App\Services\TelegramFormatterService;
use App\Services\MarkdownFormatterService;
use Illuminate\Console\Command;

class TestTelegramFormattingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:telegram-formatting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование форматирования сообщений для Telegram';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Тестирование форматирования сообщений для Telegram...');
        
        // Тестовые случаи с markdown разметкой
        $testCases = [
            'Простой текст без проблемных символов',
            '# Заголовок первого уровня
## Заголовок второго уровня
### Заголовок третьего уровня',
            'Текст с **жирным** и *курсивом* форматированием',
            'Код: `echo "Hello World"` и блок кода:
```php
function test() {
    return "test";
}
```',
            'Список элементов:
- Первый элемент
- Второй элемент
- Третий элемент

Нумерованный список:
1. Первый пункт
2. Второй пункт
3. Третий пункт',
            'Ссылка: [Google](https://google.com) и цитата:
> Это важная цитата
> которая занимает несколько строк',
            'Горизонтальная линия:
---
Текст после линии',
            'Очень длинный текст с markdown. ' . str_repeat('**Жирный текст** и *курсив*. ', 50),
        ];
        
        foreach ($testCases as $index => $testText) {
            $this->info("\n--- Тест кейс " . ($index + 1) . " ---");
            $this->line("Исходный: " . mb_substr($testText, 0, 100) . (mb_strlen($testText) > 100 ? '...' : ''));
            
            // Тестируем экранирование
            $escaped = TelegramFormatterService::escapeMarkdown($testText);
            $this->line("Экранированный: " . mb_substr($escaped, 0, 100) . (mb_strlen($escaped) > 100 ? '...' : ''));
            
            // Тестируем конвертацию markdown в обычный текст
            $plainText = TelegramFormatterService::convertMarkdownToPlainText($testText);
            $this->line("Конвертированный текст: " . mb_substr($plainText, 0, 100) . (mb_strlen($plainText) > 100 ? '...' : ''));
            
            // Тестируем форматирование
            $formatted = TelegramFormatterService::formatForTelegram($testText, false);
            $this->line("Обычное форматирование: OK");
            
            $formattedMarkdown = TelegramFormatterService::formatForTelegram($testText, true);
            $this->line("Markdown форматирование: " . ($formattedMarkdown['parse_mode'] ?? 'none'));
            
            // Тестируем разбивку длинных сообщений
            $parts = TelegramFormatterService::splitLongMessage($testText, 200);
            $this->line("Частей сообщения: " . count($parts));
            
            // Тестируем HTML форматирование для веб-интерфейса
            $hasMarkdown = MarkdownFormatterService::hasMarkdown($testText);
            $this->line("Содержит markdown: " . ($hasMarkdown ? 'Да' : 'Нет'));
            
            if ($hasMarkdown) {
                $htmlFormatted = MarkdownFormatterService::convertMarkdownToHtml($testText);
                $this->line("HTML форматирование: " . mb_substr(strip_tags($htmlFormatted), 0, 100) . (mb_strlen(strip_tags($htmlFormatted)) > 100 ? '...' : ''));
            }
        }
        
        $this->info("\n✅ Тестирование завершено!");
        $this->warn("\n💡 Рекомендация: Используйте formatForTelegram() с параметром false для обычного текста");
        $this->warn("   чтобы избежать проблем с markdown форматированием.");
    }
}
