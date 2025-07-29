<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

class TelegramBotManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static string $view = 'filament.pages.telegram-bot-manager';
    protected static ?string $title = 'Управление Telegram ботом';
    protected static ?string $navigationLabel = 'Telegram бот';
    protected static ?string $navigationGroup = 'Система';
    protected static ?int $navigationSort = 10;

    protected function getHeaderActions(): array
    {
        $botActive = Cache::get('telegram_bot_active', false);
        
        return [
            Action::make('toggleBot')
                ->label($botActive ? 'Остановить бота' : 'Запустить бота')
                ->icon($botActive ? 'heroicon-o-stop-circle' : 'heroicon-o-play-circle')
                ->color($botActive ? 'danger' : 'success')
                ->action(function () {
                    $this->toggleBotStatus();
                })
                ->requiresConfirmation()
                ->modalHeading($botActive ? 'Остановить Telegram бота?' : 'Запустить Telegram бота?')
                ->modalDescription('Вы уверены, что хотите изменить состояние бота?')
                ->modalSubmitActionLabel('Да, продолжить'),
                
            Action::make('refreshStatus')
                ->label('Обновить статус')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->checkBotStatus();
                    Notification::make()
                        ->title('Статус обновлен')
                        ->success()
                        ->send();
                }),
                
            Action::make('viewLogs')
                ->label('Просмотр логов')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->action(function () {
                    try {
                        $logPath = storage_path('logs/telegram_bot.log');
                        if (file_exists($logPath)) {
                            $logs = $this->tail($logPath, 20); // Исправлено: $this->tail
                            Notification::make()
                                ->title('Последние логи бота')
                                ->body(implode("\n", $logs)) // Отображаем логи в уведомлении
                                ->info()
                                ->persistent() // Уведомление не исчезает автоматически
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Логи не найдены')
                                ->body('Файл логов еще не создан')
                                ->warning()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Ошибка чтения логов')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function toggleBotStatus()
    {
        $currentStatus = Cache::get('telegram_bot_active', false);
        $newStatus = !$currentStatus;
        
        \Log::info('TelegramBotManager: Попытка переключения состояния бота', [
            'current_status' => $currentStatus,
            'new_status' => $newStatus,
            'user' => auth()->user()?->name ?? 'unknown'
        ]);
        
        if ($newStatus) {
            // Запускаем бота
            try {
                $basePath = base_path();
                $phpPath = PHP_BINARY;
                $command = "cd {$basePath} && nohup {$phpPath} artisan telegram:poll --daemon > storage/logs/telegram_bot.log 2>&1 & echo $!";
                
                \Log::info('TelegramBotManager: Выполняем команду запуска', ['command' => $command]);
                
                $pid = trim(shell_exec($command));
                
                \Log::info('TelegramBotManager: Результат команды запуска', ['pid' => $pid]);
                
                if ($pid && is_numeric($pid)) {
                    Cache::put('telegram_bot_active', true);
                    Cache::put('telegram_bot_process_id', (int)$pid);
                    Cache::put('telegram_bot_started_at', now());
                    
                    \Log::info('TelegramBotManager: Бот успешно запущен', ['pid' => $pid]);
                    
                    Notification::make()
                        ->title('Бот запущен')
                        ->body('Telegram бот успешно запущен (PID: ' . $pid . ')')
                        ->success()
                        ->send();
                } else {
                    throw new \Exception('Не удалось запустить процесс. PID: ' . $pid);
                }
            } catch (\Exception $e) {
                \Log::error('TelegramBotManager: Ошибка запуска бота', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                Notification::make()
                    ->title('Ошибка запуска')
                    ->body('Не удалось запустить бота: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        } else {
            // Останавливаем бота
            try {
                Cache::put('telegram_bot_active', false);
                
                $processId = Cache::get('telegram_bot_process_id');
                if ($processId) {
                    \Log::info('TelegramBotManager: Останавливаем процесс', ['pid' => $processId]);
                    
                    if (PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Darwin') {
                        exec("kill {$processId} 2>/dev/null");
                    } elseif (PHP_OS_FAMILY === 'Windows') {
                        exec("taskkill /PID {$processId} /F 2>NUL");
                    }
                    
                    Cache::forget('telegram_bot_process_id');
                    Cache::forget('telegram_bot_started_at');
                }
                
                \Log::info('TelegramBotManager: Бот остановлен');
                
                Notification::make()
                    ->title('Бот остановлен')
                    ->body('Telegram бот успешно остановлен')
                    ->warning()
                    ->send();
                    
            } catch (\Exception $e) {
                \Log::error('TelegramBotManager: Ошибка остановки бота', [
                    'error' => $e->getMessage()
                ]);
                
                Notification::make()
                    ->title('Ошибка остановки')
                    ->body('Ошибка при остановке бота: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }

    public function checkBotStatus()
    {
        $botActive = Cache::get('telegram_bot_active', false);
        $processId = Cache::get('telegram_bot_process_id');
        $lastHeartbeat = Cache::get('telegram_bot_last_heartbeat');
        $startedAt = Cache::get('telegram_bot_started_at');
        
        // Проверяем процесс
        $processExists = false;
        if ($processId) {
            if (PHP_OS_FAMILY === 'Windows') {
                $output = shell_exec("tasklist /FI \"PID eq {$processId}\" 2>NUL");
                $processExists = $output && strpos($output, (string)$processId) !== false;
            } else {
                $processExists = file_exists("/proc/{$processId}");
            }
        }
        
        if ($botActive && !$processExists) {
            Cache::put('telegram_bot_active', false);
            Cache::forget('telegram_bot_process_id');
            Cache::forget('telegram_bot_started_at');
        }
        
        return [
            'active' => $botActive,
            'process_exists' => $processExists,
            'process_id' => $processId,
            'started_at' => $startedAt,
            'last_heartbeat' => $lastHeartbeat
        ];
    }
    
    public function getBotStatusForView()
    {
        return $this->checkBotStatus();
    }
    
    private function tail($file, $lines = 20)
    {
        if (!file_exists($file)) {
            return [];
        }
        
        $handle = fopen($file, 'r');
        $linecounter = 0;
        $pos = -2;
        $beginning = false;
        $text = [];
        
        while ($linecounter < $lines) {
            $t = ' ';
            while ($t != "\n") {
                if (fseek($handle, $pos, SEEK_END) == -1) {
                    $beginning = true;
                    break;
                }
                $t = fgetc($handle);
                $pos--;
            }
            $linecounter++;
            if ($beginning) {
                rewind($handle);
            }
            $text[$lines - $linecounter] = fgets($handle);
            if ($beginning) break;
        }
        fclose($handle);
        return array_reverse($text);
    }
}