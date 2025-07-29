<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestScribeApiCommand extends Command
{
    protected $signature = 'test:scribe-api {--user-id=1}';
    protected $description = 'Тестирование API эндпоинтов через Scribe документацию';

    public function handle()
    {
        $userId = $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Пользователь с ID {$userId} не найден");
            return 1;
        }

        $this->info("🧪 Тестирование API эндпоинта /chat/send");
        $this->info("👤 Пользователь: {$user->name}");
        
        // Создаем токен для тестирования
        $token = $user->createToken('test-scribe')->plainTextToken;
        $this->line("🔑 Токен создан");

        $baseUrl = config('app.url');

        $testCases = [
            [
                'name' => 'Без document_ids (как в Scribe документации)',
                'data' => [
                    'message' => 'Расскажи о содержимом документов'
                ]
            ],
            [
                'name' => 'С пустым массивом document_ids',
                'data' => [
                    'message' => 'Расскажи о содержимом документов',
                    'document_ids' => []
                ]
            ]
        ];

        foreach ($testCases as $testCase) {
            $this->info("\n--- {$testCase['name']} ---");
            
            try {
                $response = Http::withToken($token)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->post("{$baseUrl}/api/v1/chat/send", $testCase['data']);

                $this->line("HTTP Code: " . $response->status());
                
                if ($response->successful()) {
                    $data = $response->json();
                    if ($data && isset($data['success']) && $data['success']) {
                        $this->line("✅ Запрос успешен");
                        $this->line("📝 Ответ: " . substr($data['data']['bot_response']['message'], 0, 100) . "...");
                        
                        $contextDocs = $data['data']['bot_response']['context_documents'];
                        if ($contextDocs) {
                            $this->line("📚 Использованы документы: " . implode(', ', $contextDocs));
                        }
                    } else {
                        $this->error("❌ Ошибка в ответе: " . ($data['message'] ?? 'Неизвестная ошибка'));
                    }
                } else {
                    $this->error("❌ HTTP ошибка");
                    $this->line("Response: " . substr($response->body(), 0, 200) . "...");
                }
            } catch (\Exception $e) {
                $this->error("❌ Исключение: " . $e->getMessage());
            }
        }

        // Удаляем тестовый токен
        $user->tokens()->where('name', 'test-scribe')->delete();
        
        $this->info("\n✅ Тестирование завершено!");
        return 0;
    }
}