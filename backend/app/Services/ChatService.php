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
    private GigaChatService $gigaChatService;
    private OpenRouterService $openRouterService;
    private SearchService $searchService;

    public function __construct(
        YandexGptService $yandexGptService, 
        GigaChatService $gigaChatService, 
        OpenRouterService $openRouterService,
        SearchService $searchService
    ) {
        $this->yandexGptService = $yandexGptService;
        $this->gigaChatService = $gigaChatService;
        $this->openRouterService = $openRouterService;
        $this->searchService = $searchService;
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
            // Проверяем, есть ли документы в системе
            $documentsCount = DocumentEmbedding::count();
            
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
        
        $model = config('app.default_ai_model', env('DEFAULT_AI_MODEL', 'yandex'));
        
        $startTime = microtime(true);
        
        // Используем унифицированный SearchService для поиска релевантных документов
        $relevantDocuments = $this->searchService->findRelevantDocuments($user, $message, 5, $selectedDocuments);
        
        if ($relevantDocuments->isEmpty()) {
            // Если релевантных документов не найдено, используем простой ответ
            return [$this->generateSimpleResponse($message), null];
        }
        
        // Формируем контекст из найденных документов
        $context = $this->searchService->buildContext($relevantDocuments);
        
        // Получаем названия документов для сохранения в контексте
        $contextDocumentTitles = $this->searchService->getDocumentTitles($relevantDocuments);
        
        // Логируем качество поиска
        $executionTime = microtime(true) - $startTime;
        $this->searchService->logSearchQuality($message, $relevantDocuments, $executionTime);

        $response = "";

        switch (strtolower($model)) {
            case 'gigachat':
                $response = $this->gigaChatService->generateResponse($context, $message, $user->id);
            case 'openrouter':
                $response = $this->openRouterService->generateResponse($context, $message, $user->id);
            case 'yandex':
            default:
                $response = $this->yandexGptService->generateResponse($context, $message, $user->id);
        }
        
        return [$response, $contextDocumentTitles];
    }



    /**
     * Получить историю чата конкретного пользователя
     */
    public function getUserChatHistory(int $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return ChatMessage::getUserChatHistory($userId, $limit);
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