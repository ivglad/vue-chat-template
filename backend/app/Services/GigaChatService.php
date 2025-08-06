<?php

namespace App\Services;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GigaChatService
{
    private string $authKey;
    private string $scope;

    public function __construct()
    {
        $this->authKey = config('services.gigachat.auth_key');
        $this->scope = config('services.gigachat.scope', 'GIGACHAT_API_PERS');
    }

    /**
     * Получение токена доступа
     */
    public function getToken(): ?string
    {
        return Cache::remember('gigachat_access_token', 3500, function () {
            return $this->fetchNewToken();
        });
    }

    /**
     * Получение нового токена
     */
    private function fetchNewToken(): ?string
    {
        $rqUid = (string) Str::uuid();

        if (!$this->authKey) {
            Log::error('GIGACHAT_AUTH_KEY не задан в .env');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Basic {$this->authKey}",
                'RqUID' => $rqUid,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ])
            ->withoutVerifying()
            ->asForm()
            ->post('https://ngw.devices.sberbank.ru:9443/api/v2/oauth', [
                'scope' => $this->scope,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }

            Log::error('Не удалось получить токен GigaChat: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Ошибка получения токена GigaChat: ' . $e->getMessage());
            return null;
        }
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
    public function generateResponse(string $context, string $question, ?int $userId = null): ?string
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                Log::error('Не удалось получить токен доступа GigaChat');
                return null;
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

            $response = Http::withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->withoutVerifying()
                ->timeout(60)
                ->post('https://gigachat.devices.sberbank.ru/api/v1/chat/completions', [
                    'model' => 'GigaChat',
                    'messages' => $messages,
                    'stream' => false,
                    'repetition_penalty' => 1,
                    'temperature' => 0.3,
                    'max_tokens' => 2000,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!isset($data['choices'][0]['message']['content'])) {
                    Log::error('GigaChat completion response invalid', ['response' => $data]);
                    return null;
                }
                
                return $data['choices'][0]['message']['content'];
            } else {
                Log::error('GigaChat API error: ' . $response->status() . ' - ' . $response->body());
                return null;
            }

        } catch (\Exception $e) {
            Log::error('GigaChat completion error: ' . $e->getMessage(), [
                'context_length' => mb_strlen($context),
                'question_length' => mb_strlen($question),
                'user_id' => $userId,
            ]);
            return null;
        }
    }
}