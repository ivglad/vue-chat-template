<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestApiChatCommand extends Command
{
    protected $signature = 'test:api-chat {--user-id=1}';
    protected $description = 'Тестирование API чата с различными параметрами document_ids';

    public function handle()
    {
        $userId = $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Пользователь с ID {$userId} не найден");
            return 1;
        }

        $this->info("🧪 Тестирование API чата для пользователя: {$user->name}");
        
        // Получаем доступные документы пользователя
        $availableDocuments = Document::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('roles', function ($roleQuery) use ($user) {
                    $roleQuery->whereIn('roles.id', $user->roles->pluck('id'));
                });
        })
        ->where('embeddings_generated', true)
        ->get();

        $this->info("📚 Доступных документов: " . $availableDocuments->count());
        
        if ($availableDocuments->isEmpty()) {
            $this->warn("⚠️  У пользователя нет доступных документов с эмбеддингами");
            return 0;
        }

        $availableDocuments->each(function ($doc) {
            $this->line("  - {$doc->title} (ID: {$doc->id})");
        });

        // Создаем токен для пользователя (для тестирования)
        $token = $user->createToken('test-token')->plainTextToken;
        $baseUrl = config('app.url');

        $testCases = [
            [
                'name' => 'Без указания document_ids (поиск по всем документам)',
                'data' => [
                    'message' => 'Расскажи кратко о содержимом документов'
                ]
            ],
            [
                'name' => 'С пустым массивом document_ids',
                'data' => [
                    'message' => 'Что содержится в документах?',
                    'document_ids' => []
                ]
            ]
        ];

        // Добавляем тест с конкретными документами, если они есть
        if ($availableDocuments->count() > 0) {
            $firstDocId = $availableDocuments->first()->id;
            $testCases[] = [
                'name' => "С указанием конкретного документа (ID: {$firstDocId})",
                'data' => [
                    'message' => 'Расскажи о содержимом этого документа',
                    'document_ids' => [$firstDocId]
                ]
            ];
        }

        foreach ($testCases as $index => $testCase) {
            $this->info("\n--- Тест кейс " . ($index + 1) . ": {$testCase['name']} ---");
            
            try {
                $response = Http::withToken($token)
                    ->post("{$baseUrl}/api/v1/chat/send", $testCase['data']);

                if ($response->successful()) {
                    $data = $response->json();
                    $this->line("✅ Запрос успешен");
                    $this->line("📝 Ответ бота: " . mb_substr($data['data']['bot_response']['message'], 0, 100) . '...');
                    
                    $contextDocs = $data['data']['bot_response']['context_documents'];
                    if ($contextDocs) {
                        $this->line("📚 Использованы документы: " . implode(', ', $contextDocs));
                    } else {
                        $this->line("📚 Контекст документов: не использован");
                    }
                } else {
                    $this->error("❌ Ошибка запроса: " . $response->status());
                    $this->line($response->body());
                }
            } catch (\Exception $e) {
                $this->error("❌ Исключение: " . $e->getMessage());
            }
        }

        // Удаляем тестовый токен
        $user->tokens()->where('name', 'test-token')->delete();
        
        $this->info("\n✅ Тестирование завершено!");
        return 0;
    }
}