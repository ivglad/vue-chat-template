<?php

namespace App\Services;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    private string $apiKey;
    private string $baseUrl = 'https://openrouter.ai/api/v1';
    private array $availableModels;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key');
        $this->availableModels = config('services.openrouter.models', [
            'qwen/qwen3-coder:free' => 'Qwen3 Coder (Free)',
            'meta-llama/llama-3.2-3b-instruct:free' => 'Llama 3.2 3B (Free)',
            'microsoft/phi-3-mini-128k-instruct:free' => 'Phi-3 Mini (Free)',
            'google/gemma-2-9b-it:free' => 'Gemma 2 9B (Free)',
            'mistralai/mistral-7b-instruct:free' => 'Mistral 7B (Free)',
            'huggingfaceh4/zephyr-7b-beta:free' => 'Zephyr 7B Beta (Free)',
            'openchat/openchat-7b:free' => 'OpenChat 7B (Free)',
            'gryphe/mythomist-7b:free' => 'MythoMist 7B (Free)',
            'undi95/toppy-m-7b:free' => 'Toppy M 7B (Free)',
            'openrouter/auto' => 'Auto (Best Available)',
        ]);
    }

    /**
     * Получить список доступных моделей
     */
    public function getAvailableModels(): array
    {
        return $this->availableModels;
    }

    /**
     * Получение последних сообщений пользователя для контекста
     */
    private function getChatHistory(int $userId, int $limit = 10): array
    {
        $messages = ChatMessage::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit * 2)
            ->get()
            ->reverse();

        $history = [];
        foreach ($messages as $message) {
            if ($message->type === 'user') {
                $history[] = [
                    'role' => 'user',
                    'content' => $message->message
                ];
            } elseif ($message->type === 'bot' && !empty($message->message)) {
                $history[] = [
                    'role' => 'assistant',
                    'content' => $message->message
                ];
            }
        }

        return array_slice($history, -$limit);
    }

    /**
     * Генерация ответа на основе контекста и вопроса
     */
    public function generateResponse(string $context, string $question, ?int $userId = null, ?string $model = null): ?string
    {
        try {
            // Проверяем наличие API ключа
            if (empty($this->apiKey)) {
                Log::error('OpenRouter API key not configured');
                return null;
            }

            // Используем модель по умолчанию, если не указана
            if (!$model) {
                $model = config('services.openrouter.default_model', 'qwen/qwen3-coder:free');
            }

            // Получаем историю чата пользователя, если передан userId
            $messages = [];
            if ($userId) {
                $chatHistory = $this->getChatHistory($userId, 10);
                $messages = array_merge($messages, $chatHistory);
            }

            // Формируем текущий промпт в зависимости от наличия контекста
            if (empty($context)) {
                $currentPrompt = $question;
            } else {
                $currentPrompt = "Контекст: {$context}\n\nВопрос: {$question}\n\nОтветь на вопрос, используя только информацию из контекста. Если в контексте нет информации для ответа, скажи об этом.";
            }

            // Добавляем текущий вопрос к сообщениям
            $messages[] = [
                'role' => 'user',
                'content' => $currentPrompt,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url', 'http://localhost'),
                'X-Title' => config('app.name', 'Laravel App'),
            ])
            ->timeout(60)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.3,
                'max_tokens' => 2000,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!isset($data['choices'][0]['message']['content'])) {
                    Log::error('OpenRouter completion response invalid', ['response' => $data]);
                    return null;
                }
                
                return $data['choices'][0]['message']['content'];
            } else {
                Log::error('OpenRouter API error: ' . $response->status() . ' - ' . $response->body());
                return null;
            }

        } catch (\Exception $e) {
            Log::error('OpenRouter completion error: ' . $e->getMessage(), [
                'context_length' => mb_strlen($context),
                'question_length' => mb_strlen($question),
                'user_id' => $userId,
                'model' => $model,
            ]);
            return null;
        }
    }

    /**
     * Тестирование подключения к API
     */
    public function testConnection(?string $model = null): array
    {
        try {
            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'message' => 'API ключ не настроен'
                ];
            }

            if (!$model) {
                $model = config('services.openrouter.default_model', 'qwen/qwen3-coder:free');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url', 'http://localhost'),
                'X-Title' => config('app.name', 'Laravel App'),
            ])
            ->timeout(10)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Тест подключения'
                    ]
                ],
                'max_tokens' => 10,
                'temperature' => 0.1,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Подключение успешно'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка API: ' . $response->status() . ' - ' . $response->body()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка подключения: ' . $e->getMessage()
            ];
        }
    }
}