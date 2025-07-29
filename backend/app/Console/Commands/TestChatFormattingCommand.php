<?php

namespace App\Console\Commands;

use App\Services\ChatService;
use App\Services\TelegramFormatterService;
use App\Services\MarkdownFormatterService;
use App\Models\User;
use Illuminate\Console\Command;

class TestChatFormattingCommand extends Command
{
    protected $signature = 'test:chat-formatting {--user-id=1}';
    protected $description = 'Тестирование форматирования ответов чата';

    public function handle()
    {
        $userId = $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Пользователь с ID {$userId} не найден");
            return 1;
        }

        $this->info("🧪 Тестирование форматирования ответов чата для пользователя: {$user->name}");
        
        $testQuestions = [
            'Расскажи о Laravel фреймворке',
            'Как создать миграцию в Laravel?',
            'Объясни принципы работы MVC',
        ];

        $chatService = app(ChatService::class);

        foreach ($testQuestions as $index => $question) {
            $this->info("\n--- Тест вопрос " . ($index + 1) . " ---");
            $this->line("Вопрос: {$question}");
            
            $this->line("⏳ Получаем ответ от ИИ...");
            $response = $chatService->processMessage($user, $question);
            
            if (!$response) {
                $this->error("Не удалось получить ответ");
                continue;
            }

            $this->line("\n📝 Исходный ответ:");
            $this->line(mb_substr($response, 0, 200) . (mb_strlen($response) > 200 ? '...' : ''));
            
            // Тестируем форматирование для Telegram
            $telegramFormatted = TelegramFormatterService::formatForTelegram($response, false);
            $this->line("\n📱 Telegram форматирование:");
            $this->line(mb_substr($telegramFormatted['text'], 0, 200) . (mb_strlen($telegramFormatted['text']) > 200 ? '...' : ''));
            
            // Тестируем HTML форматирование для веб-интерфейса
            $hasMarkdown = MarkdownFormatterService::hasMarkdown($response);
            $this->line("\n🌐 Веб-интерфейс (содержит markdown: " . ($hasMarkdown ? 'Да' : 'Нет') . "):");
            
            if ($hasMarkdown) {
                $htmlFormatted = MarkdownFormatterService::convertMarkdownToHtml($response);
                $plainHtml = strip_tags($htmlFormatted);
                $this->line(mb_substr($plainHtml, 0, 200) . (mb_strlen($plainHtml) > 200 ? '...' : ''));
            } else {
                $this->line("Обычный текст без форматирования");
            }
            
            // Тестируем разбивку длинных сообщений
            $parts = TelegramFormatterService::splitLongMessage($response);
            $this->line("\n📊 Статистика:");
            $this->line("- Длина ответа: " . mb_strlen($response) . " символов");
            $this->line("- Частей для Telegram: " . count($parts));
            $this->line("- Содержит markdown: " . ($hasMarkdown ? 'Да' : 'Нет'));
        }
        
        $this->info("\n✅ Тестирование завершено!");
        return 0;
    }
}