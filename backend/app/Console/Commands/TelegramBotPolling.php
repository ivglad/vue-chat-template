<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class TelegramBotPolling extends Command
{
    use TelegramUserTrait;
    
    protected $signature = 'telegram:poll {--daemon : Запустить в режиме демона}';
    protected $description = 'Запускает пулинг для Telegram бота';
    
    protected $lastUpdateId = 0;
    protected $shouldStop = false;

    public function handle()
    {
        $isDaemon = $this->option('daemon');
        
        // Регистрируем обработчики сигналов для корректной остановки
        $this->registerSignalHandlers();
        
        // Устанавливаем состояние бота как активное
        Cache::put('telegram_bot_active', true);
        Cache::put('telegram_bot_last_heartbeat', now());
        
        $this->info('Запуск Telegram бота...');

        try {
            $botInfo = Telegram::bot('mybot')->getMe();
            $this->info('Бот подключен: @' . $botInfo->username);
            
            while (!$this->shouldStop) {
                // Обрабатываем сигналы
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
                
                // Проверяем, активен ли бот
                if (!Cache::get('telegram_bot_active', false)) {
                    $this->info('Бот деактивирован администратором. Завершение работы...');
                    break;
                }
                
                // Обновляем heartbeat
                Cache::put('telegram_bot_last_heartbeat', now());
                
                try {
                   
                    $updates = Telegram::bot('mybot')->getUpdates([
                        'offset' => $this->lastUpdateId + 1,
                        'timeout' => 30,
                    ]);
                    
                    if (count($updates) > 0) {

                        $this->info('Получено обновлений: ' . count($updates));
                        
                        foreach ($updates as $update) {
                            $this->lastUpdateId = $update->updateId;
                            
                            // Еще раз проверяем состояние перед обработкой каждого сообщения
                            if (!Cache::get('telegram_bot_active', false) || $this->shouldStop) {
                                $this->info('Бот деактивирован во время обработки. Завершение...');
                                return 0;
                            }
                            
                            // Проверяем, есть ли в обновлении сообщение
                            if ($update->message) {
                                $messageText = $update->message->text ?? '';
                                
                                // Если это команда, обрабатываем стандартным способом
                                if (strpos($messageText, '/') === 0) {
                                    Telegram::bot('mybot')->processCommand($update);
                                } else {
                                    // Если это обычное сообщение, обрабатываем через ChatService
                                    $this->handleRegularMessage($update);
                                }
                            } else {
                                // Обрабатываем другие типы обновлений (например, callback queries)
                                Telegram::bot('mybot')->processCommand($update);
                            }
                        }
                    }
                } catch (Exception $e) {
                    \Log::error('Ошибка при обработке обновления: ' . $e->getMessage());
                    $this->error('Ошибка: ' . $e->getMessage());
                    
                    // Если не режим демона, выходим при ошибке
                    if (!$isDaemon) {
                        Cache::put('telegram_bot_active', false);
                        return 1;
                    }
                    
                    sleep(5);
                }
            }
            
        } catch (Exception $e) {
            \Log::error('Ошибка подключения к боту: ' . $e->getMessage());
            $this->error('Ошибка подключения: ' . $e->getMessage());
            Cache::put('telegram_bot_active', false);
            return 1;
        }
        
        // Устанавливаем состояние как неактивное при завершении
        Cache::put('telegram_bot_active', false);
        Cache::forget('telegram_bot_last_heartbeat');
        $this->info('Telegram бот остановлен.');
        return 0;
    }
    
    /**
     * Регистрируем обработчики сигналов для корректной остановки
     */
    private function registerSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, function($signal) {
                $this->info("Получен сигнал завершения: {$signal}");
                $this->shouldStop = true;
            });
            pcntl_signal(SIGINT, function($signal) {
                $this->info("Получен сигнал завершения: {$signal}");
                $this->shouldStop = true;
            });
            pcntl_signal(SIGQUIT, function($signal) {
                $this->info("Получен сигнал завершения: {$signal}");
                $this->shouldStop = true;
            });
        }
    }

    /**
     * Обработка обычных сообщений (не команд)
     */
    private function handleRegularMessage(Update $update)
    {
        try {
            $message = $update->message;
            $telegramUser = $message->from;
            $messageText = $message->text ?? '';

            // Найти или создать пользователя
            $user = $this->findTelegramUser($telegramUser);

            if (!$user) {
                // Если пользователь не найден, предлагаем начать с /start
                Telegram::bot('mybot')->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => 'Пожалуйста, сначала выполните команду /start для регистрации в системе.'
                ]);
                return;
            }

            // Обновляем время последней активности
            $this->updateUserLastSeen($user);

            if (empty(trim($messageText))) {
                Telegram::bot('mybot')->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => 'Пожалуйста, отправьте ваш вопрос текстом.'
                ]);
                return;
            }

            // Показываем индикатор "печатает..."
            Telegram::bot('mybot')->sendChatAction([
                'chat_id' => $message->chat->id,
                'action' => 'typing'
            ]);

            // Telegram::bot('mybot')->sendMessage([
            //     'chat_id' => $message->chat->id,
            //     'text' => '⏳ Обрабатываю ваш вопрос, пожалуйста подождите...'
            // ]);

            // Обрабатываем сообщение через ChatService
            $chatService = app(\App\Services\ChatService::class);
            $answer = $chatService->processMessage($user, $messageText);

            if ($answer) {
                Telegram::bot('mybot')->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => $answer,
                    'parse_mode' => 'HTML'
                ]);
                
                // Добавляем справочное сообщение в конце
                // if (count($messageParts) > 0) {
                //     Telegram::bot('mybot')->sendMessage([
                //         'chat_id' => $message->chat->id,
                //         'text' => "\n💡 Совет: используйте /ask для более точных ответов по документам!"
                //     ]);
                // }
            } else {
                Telegram::bot('mybot')->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => '😔 Извините, не удалось получить ответ на ваш вопрос...'
                ]);
            }

        } catch (Exception $e) {
            \Log::error('Error in handleRegularMessage: ' . $e->getMessage(), [
                'update' => $update->toArray(),
                'exception' => $e
            ]);
            
            if (isset($message)) {
                Telegram::bot('mybot')->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => '❌ Произошла ошибка при обработке вашего сообщения.'
                ]);
            }
        }
    }
}