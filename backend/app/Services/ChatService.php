<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Document;
use App\Models\DocumentEmbedding;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ChatService
{
    private YandexGptService $yandexGptService;

    public function __construct(YandexGptService $yandexGptService)
    {
        $this->yandexGptService = $yandexGptService;
    }

    /**
     * Обработать сообщение пользователя и сгенерировать ответ
     */
    public function processMessage(User $user, string $message): ?string
    {
        return $this->processMessageWithDocuments($user, $message, []);
    }

    /**
     * Обработать сообщение пользователя с выбранными документами и сгенерировать ответ
     */
    public function processMessageWithDocuments(User $user, string $message, array $selectedDocuments = []): ?string
    {
        try {
            // Валидируем выбранные документы
            $selectedDocuments = $this->validateSelectedDocuments($user, $selectedDocuments);
            
            // Проверяем, есть ли документы в системе
            $documentsCount = DocumentEmbedding::count();
            
            if ($documentsCount === 0) {
                // Если документов нет, используем YandexGPT без контекста
                $response = 'Извините, не удалось сгенерировать ответ. Попробуйте переформулировать вопрос.';
                $contextDocuments = null;
            } else {
                // Если есть документы, ищем по ним
                [$response, $contextDocuments] = $this->generateResponseWithContext($user, $message, $selectedDocuments);
            }

            // Сохраняем диалог в базу данных с информацией о контексте
            ChatMessage::createConversation($user->id, $message, $response, $contextDocuments);

            if (!$response) {
                $response = 'Извините, не удалось сгенерировать ответ. Попробуйте переформулировать вопрос.';
            }

            return $response;
            
        } catch (\Exception $e) {
            Log::error('Chat processing error', ['error' => $e->getMessage(), 'message' => $message]);
            return 'Произошла ошибка при обработке сообщения.';
        }
    }

    /**
     * Генерировать простой ответ без контекста документов
     */
    private function generateSimpleResponse(string $message): ?string
    {
        $prompt = "Ты полезный ИИ-ассистент. Ответь на следующий вопрос максимально полно и полезно:\n\n{$message}";
        
        return $this->yandexGptService->generateResponse('', $prompt);
    }

    /**
     * Генерировать ответ с контекстом из документов
     */
    private function generateResponseWithContext(User $user, string $message, array $selectedDocuments = []): array
    {
        // Генерируем эмбеддинг для вопроса
        $questionEmbedding = $this->yandexGptService->generateEmbeddings($message);
        
        if (!$questionEmbedding) {
            Log::error('Failed to generate embeddings for chat message', ['message' => $message]);
            // Если не удалось создать эмбеддинг, используем простой ответ
            return [$this->generateSimpleResponse($message), null];
        }

        // Ищем релевантные документы с учетом выбранных
        $relevantDocuments = $this->findRelevantDocuments($questionEmbedding, $user, 5, $selectedDocuments);
        
        if ($relevantDocuments->isEmpty()) {
            // Если релевантных документов не найдено, используем простой ответ
            return [$this->generateSimpleResponse($message), null];
        }
        
        // Формируем контекст из найденных документов
        $context = $this->buildContext($relevantDocuments);
        
        // Получаем названия документов для сохранения в контексте
        $contextDocumentTitles = $relevantDocuments->map(function ($embedding) {
            return $embedding->document->title ?? 'Неизвестный документ';
        })->unique()->values()->toArray();
        
        // Генерируем ответ с помощью YandexGPT
        $response = $this->yandexGptService->generateResponse($context, $message);
        
        return [$response, $contextDocumentTitles];
    }

    /**
     * Найти релевантные документы на основе эмбеддинга вопроса
     */
    private function findRelevantDocuments(array $questionEmbedding, User $user, int $limit = 5, array $selectedDocuments = []): \Illuminate\Support\Collection
    {
        // Получаем все эмбеддинги документов, доступных пользователю
        $query = DocumentEmbedding::with('document')
            ->whereHas('document', function ($query) use ($user, $selectedDocuments) {
                $query->where(function ($subQuery) use ($user) {
                    $subQuery->where('user_id', $user->id)
                        ->orWhereHas('roles', function ($roleQuery) use ($user) {
                            $roleQuery->whereIn('roles.id', $user->roles->pluck('id'));
                        });
                });
                
                // Если указаны конкретные документы, фильтруем по ним
                if (!empty($selectedDocuments)) {
                    $query->whereIn('id', $selectedDocuments);
                }
            })
            ->whereNotNull('embedding');

        $embeddings = $query->get();

        if ($embeddings->isEmpty()) {
            return collect();
        }

        // Вычисляем сходство и сортируем по релевантности
        $scored = $embeddings->map(function ($embedding) use ($questionEmbedding) {
            // Проверяем, что эмбеддинг существует и является массивом
            if (!$embedding->embedding || !is_array($embedding->embedding)) {
                return null;
            }

            $similarity = $this->yandexGptService->cosineSimilarity(
                $questionEmbedding,
                $embedding->embedding
            );
            
            return [
                'embedding' => $embedding,
                'similarity' => $similarity,
                'document_id' => $embedding->document_id,
            ];
        })->filter(); // Убираем null значения

        // Сортируем по сходству и берем топ-N
        return $scored
            ->sortByDesc('similarity')
            ->take($limit)
            ->pluck('embedding');
    }

    /**
     * Построить контекст из релевантных документов
     */
    private function buildContext(\Illuminate\Support\Collection $relevantDocuments): string
    {
        $contextParts = [];
        
        foreach ($relevantDocuments as $embedding) {
            $document = $embedding->document;
            if ($document && $embedding->chunk_text) {
                $contextParts[] = "Документ \"{$document->title}\":\n{$embedding->chunk_text}";
            }
        }

        return implode("\n\n", $contextParts);
    }

    /**
     * Получить историю чата конкретного пользователя
     */
    public function getUserChatHistory(int $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return ChatMessage::getUserChatHistory($userId, $limit);
    }

    /**
     * Получить историю чата (устарело - оставлено для совместимости)
     */
    public function getChatHistory(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return ChatMessage::getChatHistory($limit);
    }

    /**
     * Получить список пользователей с их чатами
     */
    public function getUsersWithChats(): \Illuminate\Database\Eloquent\Collection
    {
        return ChatMessage::getUsersWithMessageCounts();
    }

    /**
     * Очистить историю чата конкретного пользователя
     */
    public function clearUserChatHistory(int $userId): bool
    {
        return ChatMessage::clearUserChatHistory($userId);
    }

    /**
     * Очистить всю историю чата (устарело - оставлено для совместимости)
     */
    public function clearChatHistory(): bool
    {
        try {
            ChatMessage::truncate();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear chat history', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Получить последние сообщения для виджета
     */
    public function getRecentMessages(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return ChatMessage::getRecentUserMessages($limit);
    }

    /**
     * Получить статистику чатов
     */
    public function getChatStatistics(): array
    {
        $totalMessages = ChatMessage::count();
        $totalUsers = ChatMessage::distinct('user_id')->count('user_id');
        $userMessages = ChatMessage::where('type', 'user')->count();
        $botMessages = ChatMessage::where('type', 'bot')->count();
        
        return [
            'total_messages' => $totalMessages,
            'total_users' => $totalUsers,
            'user_messages' => $userMessages,
            'bot_messages' => $botMessages,
            'response_rate' => $userMessages > 0 ? round(($botMessages / $userMessages) * 100, 2) : 0,
        ];
    }

    /**
     * Валидировать выбранные документы пользователя
     */
    private function validateSelectedDocuments(User $user, array $selectedDocuments): array
    {
        if (empty($selectedDocuments)) {
            return [];
        }

        // Получаем доступные документы пользователя
        $availableDocumentIds = Document::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('roles', function ($roleQuery) use ($user) {
                    $roleQuery->whereIn('roles.id', $user->roles->pluck('id'));
                });
        })
        ->where('embeddings_generated', true)
        ->pluck('id')
        ->toArray();

        // Фильтруем выбранные документы, оставляя только доступные
        $validDocuments = array_intersect($selectedDocuments, $availableDocumentIds);

        // Логируем попытки доступа к недоступным документам
        $invalidDocuments = array_diff($selectedDocuments, $availableDocumentIds);
        if (!empty($invalidDocuments)) {
            Log::warning('User tried to access unauthorized documents', [
                'user_id' => $user->id,
                'invalid_documents' => $invalidDocuments,
                'valid_documents' => $validDocuments
            ]);
        }

        return array_values($validDocuments);
    }
} 