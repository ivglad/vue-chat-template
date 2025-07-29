<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Document;
use App\Services\TelegramFormatterService;
use Telegram\Bot\Commands\Command;

class TelegramDocumentCommand extends Command
{
    use TelegramUserTrait;

    protected string $name = 'docs';
    protected string $description = 'Получить список доступных документов';

    public function handle()
    {
        $telegramUser = $this->getUpdate()->getMessage()->getFrom();
        $user = $this->findTelegramUser($telegramUser);

        if (!$user) {
            $this->replyWithMessage([
                'text' => '🔄 Пожалуйста, сначала выполните команду /start для регистрации в системе.'
            ]);
            return;
        }

        // Обновляем время последней активности
        $this->updateUserLastSeen($user);

        // Получаем документы пользователя напрямую
        $userDocuments = Document::where('user_id', $user->id)->get();
        
        // Получаем документы по ролям пользователя
        $userRoles = $user->roles()->pluck('id')->toArray();
        $roleDocuments = Document::whereHas('roles', function($query) use ($userRoles) {
            $query->whereIn('roles.id', $userRoles);
        })->get();

        // Объединяем документы
        $documents = $userDocuments->merge($roleDocuments)->unique('id');

        if ($documents->isEmpty()) {
            $message = "📚 У вас нет доступных документов.\n\n";
            $message .= "Для работы с системой:\n";
            $message .= "• Загрузите документы через админ-панель\n";
            $message .= "• Задавайте вопросы";
            
            $this->replyWithMessage(['text' => $message]);
            return;
        }

        $message = "📚 Ваши доступные документы:\n\n";
        
        foreach ($documents as $index => $document) {
            $status = $document->embeddings_generated ? "✅" : "⏳";
            $message .= "<b><a href=\"" . $document->google_docs_url ."\">" . ($index + 1) . ". " . $document->title . "</a></b>\n";
            
            
        }

        // Разбиваем длинное сообщение на части если нужно
        $messageParts = TelegramFormatterService::splitLongMessage($message);
        
        foreach ($messageParts as $part) {
            $this->replyWithMessage(['text' => $part, 'parse_mode' => 'HTML']);
            
            // Небольшая пауза между частями
            if (count($messageParts) > 1) {
                usleep(500000); // 0.5 секунды
            }
        }
    }
}
