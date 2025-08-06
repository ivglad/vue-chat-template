<?php

namespace App\Console\Commands;

use App\Services\DocumentService;
use Illuminate\Console\Command;

class TestDocumentAnswerCommand extends Command
{
    protected $signature = 'document:test-answer {user_id} {question} {--model= : Модель для ответа (yandex, gigachat или openrouter). Если не указана, используется настройка по умолчанию}';
    protected $description = 'Тестирует ответ на вопрос по документам пользователя с выбором модели';

    public function handle(DocumentService $documentService)
    {
        $userId = (int) $this->argument('user_id');
        $question = $this->argument('question');
        $model = $this->option('model');

        // Если модель не указана, получаем настройку по умолчанию
        $defaultModel = env('DEFAULT_AI_MODEL', 'yandex');
        $actualModel = $model ?: $defaultModel;

        $this->info("Тестируем ответ на вопрос для пользователя {$userId}");
        $this->info("Вопрос: {$question}");
        $this->info("Модель: {$actualModel}" . ($model ? '' : ' (по умолчанию)'));
        $this->line('');

        try {
            $answer = $documentService->answerQuestion($userId, $question, $model);

            if ($answer) {
                $this->info('✅ Ответ получен:');
                $this->line($answer);
            } else {
                $this->error('❌ Не удалось получить ответ');
            }

        } catch (\Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}