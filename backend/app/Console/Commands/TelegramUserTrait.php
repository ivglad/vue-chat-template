<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait TelegramUserTrait
{
    /**
     * Получить пользователя по Telegram данным или зарегистрировать нового
     */
    protected function findOrCreateTelegramUser($telegramUser): User
    {
        // Ищем пользователя по Telegram ID
        $user = User::where('telegram_data->id', $telegramUser->id)->first();
        
        // Если пользователь не найден, создаем нового
        if (!$user) {
            $user = $this->createTelegramUser($telegramUser);
        }
        
        return $user;
    }

    /**
     * Найти пользователя по Telegram ID
     */
    protected function findTelegramUser($telegramUser): ?User
    {
        return User::where('telegram_data->id', $telegramUser->id)->first();
    }

    /**
     * Создает нового пользователя из данных Telegram
     */
    protected function createTelegramUser($telegramUser): User
    {
        // Формируем имя пользователя
        $name = $telegramUser->first_name;
        if ($telegramUser->last_name) {
            $name .= ' ' . $telegramUser->last_name;
        }

        // Генерируем уникальный пароль
        $password = $this->generateUniquePassword();

        // Подготавливаем все данные Telegram для сохранения
        $telegramData = [
            'id' => $telegramUser->id,
            'is_bot' => $telegramUser->is_bot ?? false,
            'first_name' => $telegramUser->first_name,
            'last_name' => $telegramUser->last_name,
            'username' => $telegramUser->username,
            'language_code' => $telegramUser->language_code,
            'is_premium' => $telegramUser->is_premium ?? false,
            'added_to_attachment_menu' => $telegramUser->added_to_attachment_menu ?? false,
            'can_join_groups' => $telegramUser->can_join_groups ?? false,
            'can_read_all_group_messages' => $telegramUser->can_read_all_group_messages ?? false,
            'supports_inline_queries' => $telegramUser->supports_inline_queries ?? false,
            'can_connect_to_business' => $telegramUser->can_connect_to_business ?? false,
            'registered_at' => now()->toISOString(),
            'last_seen_at' => now()->toISOString()
        ];

        // Создаем пользователя
        $user = User::create([
            'name' => $name,
            'email' => null, // Email не обязателен для Telegram пользователей
            'password' => Hash::make($password),
            'telegram_data' => $telegramData,
        ]);

        // Присваиваем роль "пользователь"
        try {
            $user->assignRole('Пользователь');
            \Log::info('Роль "Пользователь" успешно назначена пользователю', [
                'user_id' => $user->id,
                'telegram_id' => $telegramUser->id,
                'name' => $name
            ]);
        } catch (\Exception $e) {
            \Log::error('Ошибка при назначении роли "Пользователь"', [
                'user_id' => $user->id,
                'telegram_id' => $telegramUser->id,
                'error' => $e->getMessage()
            ]);
        }

        return $user;
    }

    /**
     * Обновляет данные последней активности пользователя
     */
    protected function updateUserLastSeen(User $user): void
    {
        $telegramData = $user->telegram_data;
        $telegramData['last_seen_at'] = now()->toISOString();
        
        $user->update(['telegram_data' => $telegramData]);
    }

    /**
     * Генерирует уникальный пароль
     */
    protected function generateUniquePassword(): string
    {
        // Создаем действительно уникальный пароль
        $prefix = 'tg_';
        $timestamp = now()->timestamp;
        $random = Str::random(8);
        
        return $prefix . $timestamp . '_' . $random;
    }
} 