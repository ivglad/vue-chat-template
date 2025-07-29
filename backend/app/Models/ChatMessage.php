<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_id',
        'message',
        'response',
        'type',
        'is_processed',
        'context_documents',
    ];

    protected $casts = [
        'context_documents' => 'array',
        'is_processed' => 'boolean',
    ];

    /**
     * Отношение к пользователю
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Отношение к родительскому сообщению (для ответов бота)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'parent_id');
    }

    /**
     * Отношение к дочерним сообщениям (ответы бота на сообщение пользователя)
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'parent_id');
    }

    /**
     * Создать пару сообщений: вопрос пользователя и ответ бота
     */
    public static function createConversation(int $userId, string $message, ?string $response = null, ?array $contextDocuments = null): ?ChatMessage
    {
        // Создаем сообщение пользователя
        $userMessage = self::create([
            'user_id' => $userId,
            'message' => $message,
            'type' => 'user',
            'is_processed' => true,
            'context_documents' => $contextDocuments,
            'parent_id' => null,
        ]);

        // Создаем ответ бота, если есть
        if ($response) {
            self::create([
                'user_id' => $userId,
                'message' => $response,
                'type' => 'bot',
                'is_processed' => true,
                'context_documents' => $contextDocuments,
                'parent_id' => $userMessage->id, // Связываем с сообщением пользователя
            ]);
        }

        return $userMessage;
    }

    /**
     * Получить историю чата конкретного пользователя
     */
    public static function getUserChatHistory(int $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return self::with(['user', 'parent', 'replies'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить все сообщения для отображения в чате (устарело - оставлено для совместимости)
     */
    public static function getChatHistory(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return self::with('user')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить список пользователей с количеством сообщений
     */
    public static function getUsersWithMessageCounts(): \Illuminate\Database\Eloquent\Collection
    {
        return self::selectRaw('user_id, COUNT(*) as messages_count, MAX(created_at) as last_message_at')
            ->with('user:id,name,telegram_data')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    /**
     * Очистить историю чата пользователя
     */
    public static function clearUserChatHistory(int $userId): bool
    {
        try {
            self::where('user_id', $userId)->delete();
            return true;
        } catch (\Exception $e) {
            \Log::error('Error clearing user chat history: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Очистить всю историю чата (устарело - оставлено для совместимости)
     */
    public static function clearAllChatHistory(): bool
    {
        try {
            self::truncate();
            return true;
        } catch (\Exception $e) {
            \Log::error('Error clearing all chat history: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить последние сообщения пользователя для отображения в виджете
     */
    public static function getRecentUserMessages(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::with(['user', 'replies'])
            ->where('type', 'user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
