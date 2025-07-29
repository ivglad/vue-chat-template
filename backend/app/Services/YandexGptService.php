<?php

namespace App\Services;

use App\Models\ChatMessage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class YandexGptService
{
    private Client $client;
    private string $apiKey;
    private string $folderId;
    private string $baseUrl = 'https://llm.api.cloud.yandex.net';

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.yandex.api_key');
        $this->folderId = config('services.yandex.folder_id');
    }

    /**
     * Генерация эмбеддингов для текста
     */
    public function generateEmbeddings(string $text): ?array
    {
        try {
            // Проверяем наличие API ключей
            if (empty($this->apiKey) || empty($this->folderId)) {
                Log::error('YandexGPT API key or folder ID not configured');
                return null;
            }

            $response = $this->client->post($this->baseUrl . '/foundationModels/v1/textEmbedding', [
                'headers' => [
                    'Authorization' => 'Api-Key ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'modelUri' => "emb://{$this->folderId}/text-search-doc/latest",
                    'text' => $text,
                    'embeddingSize' => 1024
                ],
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($data['embedding'])) {
                Log::error('YandexGPT embedding response invalid', ['response' => $data]);
                return null;
            }
            
            return $data['embedding'];
        } catch (RequestException $e) {
            Log::error('YandexGPT embedding error: ' . $e->getMessage(), [
                'text_length' => mb_strlen($text),
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null
            ]);
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
            ->limit($limit * 2) // Берем больше, чтобы учесть пары вопрос-ответ
            ->get()
            ->reverse(); // Переворачиваем для хронологического порядка

        $history = [];
        foreach ($messages as $message) {
            if ($message->type === 'user') {
                $history[] = [
                    'role' => 'user',
                    'text' => $message->message
                ];
            } elseif ($message->type === 'bot' && !empty($message->message)) {
                $history[] = [
                    'role' => 'assistant',
                    'text' => $message->message
                ];
            }
        }

        // Ограничиваем до последних 10 сообщений
        return array_slice($history, -$limit);
    }

    /**
     * Генерация ответа на основе контекста и вопроса
     */
    public function generateResponse(string $context, string $question, ?int $userId = null): ?string
    {
        try {
            // Проверяем наличие API ключей
            if (empty($this->apiKey) || empty($this->folderId)) {
                Log::error('YandexGPT API key or folder ID not configured');
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
                'text' => $currentPrompt,
            ];

            $response = $this->client->post($this->baseUrl . '/foundationModels/v1/completion', [
                'headers' => [
                    'Authorization' => 'Api-Key ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'modelUri' => "gpt://{$this->folderId}/yandexgpt-lite/latest",
                    'completionOptions' => [
                        'stream' => false,
                        'temperature' => 0.3,
                        'maxTokens' => 2000,
                    ],
                    'messages' => $messages
                ],
                'timeout' => 60,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($data['result']['alternatives'][0]['message']['text'])) {
                Log::error('YandexGPT completion response invalid', ['response' => $data]);
                return null;
            }
            
            $responseText = $data['result']['alternatives'][0]['message']['text'];
            
            return $responseText;
        } catch (RequestException $e) {
            Log::error('YandexGPT completion error: ' . $e->getMessage(), [
                'context_length' => mb_strlen($context),
                'question_length' => mb_strlen($question),
                'user_id' => $userId,
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null
            ]);
            return null;
        }
    }

    /**
     * Вычисление косинусного сходства между двумя векторами
     */
    public function cosineSimilarity(array $vector1, array $vector2): float
    {
        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        for ($i = 0; $i < count($vector1); $i++) {
            $dotProduct += $vector1[$i] * $vector2[$i];
            $norm1 += $vector1[$i] * $vector1[$i];
            $norm2 += $vector2[$i] * $vector2[$i];
        }

        if ($norm1 == 0 || $norm2 == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($norm1) * sqrt($norm2));
    }
} 