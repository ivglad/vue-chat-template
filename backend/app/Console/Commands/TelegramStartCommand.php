<?php

namespace App\Console\Commands;

use App\Models\User;
use Telegram\Bot\Commands\Command;

class TelegramStartCommand extends Command
{
    use TelegramUserTrait;

    protected string $name = 'start';
    protected string $description = 'Приветствие и справка по боту';

    public function handle()
    {
        $telegramUser = $this->getUpdate()->getMessage()->getFrom();
        
        // Получаем пользователя или создаем нового
        $user = $this->findOrCreateTelegramUser($telegramUser);
        $isNewUser = $user->wasRecentlyCreated;
        
        // Обновляем время последней активности
        $this->updateUserLastSeen($user);
        
        $welcomeMessage = "";
        
        if ($isNewUser) {
            $welcomeMessage .= "<b>Вы успешно зарегистрированы в системе!</b>\n\n";
        } else {
            $welcomeMessage .= "<b>Добро пожаловать обратно, " . $user->name . "!</b>\n\n";
        }
        
        $documentsCount = $user->documents()->count();
        $processedCount = $user->documents()->where('embeddings_generated', true)->count();
        
        // Добавляем описание бота
        $welcomeMessage .= "Docwise+ работает в <b>демо-режиме</b> и позволяет задавать вопросы по документам.\n\n" .
                           "<blockquote>Для получения точных ответов, пожалуйста, <b>формулируйте вопросы чётко и конкретно.</b></blockquote>\n\n";
        
        // $welcomeMessage .= "📊 Ваша статистика:\n";
        // $welcomeMessage .= "📚 Документов загружено: {$documentsCount}\n";
        // $welcomeMessage .= "✅ Обработано для поиска: {$processedCount}\n\n";
        
        // $welcomeMessage .= "📖 Доступные команды:\n\n";
        // $welcomeMessage .= "🔍 /docs - просмотр ваших документов\n";
        // $welcomeMessage .= "❓ /ask [вопрос] - задать вопрос по документам\n";
        // $welcomeMessage .= "ℹ️ /start - показать это сообщение\n\n";

        $welcomeMessage .= "<b>Пример вопроса:</b>\n";
        $welcomeMessage .= "<code>Что должен соблюдать сотрудник?</code>\n\n";
        
        if ($documentsCount > 0) {
            $welcomeMessage .= "Начните с команды /docs, чтобы увидеть ваши документы!";
        } else {
            $welcomeMessage .= "Используйте /docs для списка доступных документов.";
        }

        $this->replyWithMessage([
            'text' => $welcomeMessage,
            'parse_mode' => 'HTML'
        ]);
    }
}