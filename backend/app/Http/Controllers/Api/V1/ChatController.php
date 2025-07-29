<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Document;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @group Чат
 * 
 * API для работы с чатом - получение истории, отправка сообщений, очистка истории
 */
class ChatController extends Controller
{
    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Получить историю чата с пагинацией
     * 
     * Возвращает историю сообщений пользователя с поддержкой пагинации
     * 
     * @authenticated
     * @queryParam offset integer Смещение для пагинации (по умолчанию 0) Example: 0
     * @queryParam limit integer Количество сообщений на страницу (по умолчанию 10, максимум 50) Example: 10
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "messages": [
     *       {
     *         "id": 1,
     *         "message": "Привет, как дела?",
     *         "type": "user",
     *         "context_documents": null,
     *         "created_at": "2023-12-01T10:00:00.000000Z",
     *         "replies": [
     *           {
     *             "id": 2,
     *             "message": "Привет! Дела отлично, спасибо за вопрос!",
     *             "type": "bot",
     *             "context_documents": ["Документ 1", "Документ 2"],
     *             "created_at": "2023-12-01T10:00:05.000000Z"
     *           }
     *         ]
     *       }
     *     ],
     *     "pagination": {
     *       "offset": 0,
     *       "limit": 10,
     *       "total": 25,
     *       "has_more": true
     *     }
     *   }
     * }
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'offset' => 'integer|min:0',
            'limit' => 'integer|min:1|max:50',
        ]);

        $user = $request->user();
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 10);

        // Получаем сообщения с пагинацией
        $messages = ChatMessage::where('user_id', $user->id)
            ->with(['user', 'replies'])
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        // Получаем общее количество сообщений
        $total = ChatMessage::where('user_id', $user->id)->count();

        // Форматируем сообщения для вывода
        $formattedMessages = $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'message' => $message->message,
                'type' => $message->type,
                'context_documents' => $message->context_documents,
                'created_at' => $message->created_at->toISOString(),
                'replies' => $message->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'message' => $reply->message,
                        'type' => $reply->type,
                        'context_documents' => $reply->context_documents,
                        'created_at' => $reply->created_at->toISOString(),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $formattedMessages,
                'pagination' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'total' => $total,
                    'has_more' => ($offset + $limit) < $total,
                ],
            ],
        ]);
    }

    /**
     * Отправить сообщение в чат
     * 
     * Отправляет сообщение в чат и получает ответ от ИИ с использованием контекста документов.
     * 
     * Поведение параметра document_ids:
     * - Если не указан или пустой массив - поиск происходит по всем доступным пользователю документам
     * - Если указаны конкретные ID - поиск ограничивается только этими документами
     * - Пользователь может указать только те документы, к которым у него есть доступ
     * 
     * @authenticated
     * @bodyParam message string required Текст сообщения (максимум 5000 символов) Example: Что ты знаешь о Laravel?
     * @bodyParam document_ids integer[] Массив ID документов для использования в контексте. Если не указан или пустой - поиск по всем доступным документам.
     * 
     * @response 200 scenario="Успешный ответ с поиском по всем документам" {
     *   "success": true,
     *   "data": {
     *     "user_message": {
     *       "id": 10,
     *       "message": "Что ты знаешь о Laravel?",
     *       "type": "user",
     *       "context_documents": null,
     *       "created_at": "2023-12-01T12:00:00.000000Z"
     *     },
     *     "bot_response": {
     *       "id": 11,
     *       "message": "Laravel - это популярный PHP фреймворк...",
     *       "type": "bot",
     *       "context_documents": ["Laravel Documentation", "PHP Guide"],
     *       "created_at": "2023-12-01T12:00:05.000000Z"
     *     }
     *   }
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "Нет доступа к одному или нескольким документам"
     * }
     * 
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "message": ["Поле message обязательно для заполнения."]
     *   }
     * }
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'document_ids' => 'sometimes|array',
            'document_ids.*' => 'integer|exists:documents,id',
        ]);

        $user = $request->user();
        $message = $request->get('message');
        $documentIds = $request->get('document_ids', []);

        try {
            // Проверяем, что пользователь имеет доступ к указанным документам
            if (!empty($documentIds)) {
                $userDocuments = Document::where('user_id', $user->id)
                    ->orWhereHas('roles', function ($query) use ($user) {
                        $query->whereIn('roles.id', $user->roles->pluck('id'));
                    })
                    ->whereIn('id', $documentIds)
                    ->pluck('id')
                    ->toArray();

                if (count($userDocuments) !== count($documentIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Нет доступа к одному или нескольким документам',
                    ], 403);
                }
            }

            // Обрабатываем сообщение через сервис чата
            $response = $this->chatService->processMessageWithDocuments($user, $message, $documentIds);

            if (!$response) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось обработать сообщение',
                ], 500);
            }

            // Получаем последнее сообщение пользователя с ответом
            $userMessage = ChatMessage::where('user_id', $user->id)
                ->where('type', 'user')
                ->with('replies')
                ->orderBy('created_at', 'desc')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'user_message' => [
                        'id' => $userMessage->id,
                        'message' => $userMessage->message,
                        'type' => $userMessage->type,
                        'context_documents' => $userMessage->context_documents,
                        'created_at' => $userMessage->created_at->toISOString(),
                    ],
                    'bot_response' => $userMessage->replies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'message' => $reply->message,
                            'type' => $reply->type,
                            'context_documents' => $reply->context_documents,
                            'created_at' => $reply->created_at->toISOString(),
                        ];
                    })->first(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('API Chat send error', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при обработке сообщения',
            ], 500);
        }
    }

    /**
     * Очистить историю чата пользователя
     * 
     * Удаляет всю историю сообщений текущего пользователя
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "История чата очищена"
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "Не удалось очистить историю чата"
     * }
     */
    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $result = $this->chatService->clearUserChatHistory($user->id);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'История чата очищена',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Не удалось очистить историю чата',
            ], 500);

        } catch (\Exception $e) {
            Log::error('API Chat clear error', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при очистке истории чата',
            ], 500);
        }
    }
} 