<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramFormatterService;
use Telegram\Bot\Commands\Command;

class TelegramAskCommand extends Command
{
    use TelegramUserTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected string $name = 'ask';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Задать вопрос по вашим документам';

    /**
     * Execute the console command.
     */
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

        $messageText = $this->getUpdate()->getMessage()->getText();
        $question = trim(str_replace('/ask', '', $messageText));

        if (empty($question)) {
            $this->replyWithMessage([
                'text' => 'Пожалуйста, укажите ваш вопрос после команды /ask.\n\nПример: /ask Что такое машинное обучение?'
            ]);
            return;
        }

        // Показываем индикатор "печатает..."
        $this->replyWithChatAction(['action' => 'typing']);

        // $this->replyWithMessage([
        //     'text' => '⏳ Обрабатываю ваш вопрос, пожалуйста подождите...'
        // ]);

        try {
            // Используем ChatService для единообразной обработки и записи в БД
            $chatService = app(\App\Services\ChatService::class);
            $answer = $chatService->processMessage($user, $question);

            if ($answer) {
                // Сообщение уже записано в базу данных через ChatService
                
                // Разбиваем длинный ответ на части если нужно
                $messageParts = TelegramFormatterService::splitLongMessage($answer);
                
                foreach ($messageParts as $index => $part) {
                    // Для первого сообщения добавляем заголовок
                    // if ($index === 0) {
                    //     $part = "💡 *Ответ на ваш вопрос:*\n\n" . $part;
                    // }
                    
                    // Форматируем для безопасной отправки
                    $messageData = TelegramFormatterService::formatForTelegram($part, false);
                    
                    $this->replyWithMessage($messageData);
                    
                    // Небольшая пауза между частями для избежания rate limit
                    // if ($index < count($messageParts) - 1) {
                    //     sleep(1);
                    // }
                }
                
                // Добавляем справочное сообщение в конце
                // if (count($messageParts) > 0) {
                //     $this->replyWithMessage([
                //         'text' => "\n❓ У вас есть еще вопросы? Просто напишите /ask и ваш вопрос!"
                //     ]);
                // }
            } else {
                // Сообщение уже записано в базу данных через ChatService (даже если ответ пустой)
                
                $this->replyWithMessage([
                    'text' => '😔 Извините, не удалось получить ответ на ваш вопрос.\n\nВозможные причины:\n• Нет обработанных документов\n• Вопрос не связан с содержимым документов\n• Временная проблема с сервисом\n\nПопробуйте переформулировать вопрос или обратитесь к администратору.'
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error in TelegramAskCommand: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'question' => $question,
                'exception' => $e
            ]);
            
            $this->replyWithMessage([
                'text' => '❌ Произошла ошибка при обработке вашего вопроса. Попробуйте еще раз через несколько минут или обратитесь к администратору.'
            ]);
        }
    }
}
