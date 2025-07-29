<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetupChatCommand extends Command
{
    protected $signature = 'chat:setup';
    protected $description = 'Настройка чата и создание тестового пользователя';

    public function handle(): int
    {
        $this->info('Настройка чата...');

        // Проверяем, есть ли пользователи
        $userCount = User::count();
        
        if ($userCount === 0) {
            $this->info('Создаем тестового пользователя...');
            
            $user = User::create([
                'name' => 'Тестовый пользователь',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
            ]);

            $this->info("Создан пользователь: {$user->name} ({$user->email})");
            $this->line('Пароль: password');
        } else {
            $this->info("В системе уже есть {$userCount} пользователей");
        }

        // Проверяем API ключи
        $apiKey = config('services.yandex.api_key');
        $folderId = config('services.yandex.folder_id');

        if (empty($apiKey) || empty($folderId)) {
            $this->warn('Внимание: API ключи YandexGPT не настроены!');
            $this->line('Добавьте в .env файл:');
            $this->line('YANDEX_API_KEY=ваш_ключ');
            $this->line('YANDEX_FOLDER_ID=ваш_folder_id');
        } else {
            $this->info('API ключи YandexGPT настроены');
        }

        $this->info('Настройка завершена!');
        $this->line('Теперь вы можете:');
        $this->line('1. Войти в админ-панель: /admin');
        $this->line('2. Перейти в раздел "Чат"');
        $this->line('3. Начать диалог с ИИ');

        return 0;
    }
} 