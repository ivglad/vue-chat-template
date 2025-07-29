<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class TelegramBotManagementCommand extends Command
{
    protected $signature = 'telegram:manage 
                            {action : Действие: start|stop|status|restart}
                            {--daemon : Запустить в режиме демона}';
                            
    protected $description = 'Управление Telegram ботом';

    public function handle()
    {
        $action = $this->argument('action');
        $isDaemon = $this->option('daemon');

        switch ($action) {
            case 'start':
                return $this->startBot($isDaemon);
            case 'stop':
                return $this->stopBot();
            case 'status':
                return $this->showStatus();
            case 'restart':
                $this->stopBot();
                sleep(2);
                return $this->startBot($isDaemon);
            default:
                $this->error('Неизвестное действие. Используйте: start|stop|status|restart');
                return 1;
        }
    }

    private function startBot(bool $isDaemon = false): int
    {
        $botActive = Cache::get('telegram_bot_active', false);
        
        if ($botActive) {
            $this->warn('Бот уже запущен!');
            return 0;
        }

        $this->info('Запуск Telegram бота...');
        
        $command = $isDaemon ? 'telegram:poll --daemon' : 'telegram:poll';
        
        try {
            if ($isDaemon) {
                // Запускаем в фоне через nohup
                $command = 'nohup php artisan telegram:poll --daemon > /dev/null 2>&1 & echo $!';
                $pid = trim(shell_exec($command));
                
                if ($pid && is_numeric($pid)) {
                    Cache::put('telegram_bot_active', true);
                    Cache::put('telegram_bot_process_id', (int)$pid);
                    Cache::put('telegram_bot_started_at', now());
                    
                    $this->info('Бот запущен в фоне (PID: ' . $pid . ')');
                } else {
                    $this->error('Не удалось запустить процесс в фоне');
                    return 1;
                }
            } else {
                // Запускаем в текущем процессе
                Artisan::call($command);
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Ошибка запуска бота: ' . $e->getMessage());
            return 1;
        }
    }

    private function stopBot(): int
    {
        $botActive = Cache::get('telegram_bot_active', false);
        
        if (!$botActive) {
            $this->warn('Бот уже остановлен!');
            return 0;
        }

        $this->info('Остановка Telegram бота...');
        
        // Устанавливаем флаг остановки
        Cache::put('telegram_bot_active', false);
        
        // Пытаемся завершить процесс если он есть
        $processId = Cache::get('telegram_bot_process_id');
        if ($processId) {
            try {
                if (PHP_OS_FAMILY === 'Windows') {
                    exec("taskkill /PID {$processId} /F 2>NUL");
                } else {
                    exec("kill {$processId} 2>/dev/null");
                }
                Cache::forget('telegram_bot_process_id');
                Cache::forget('telegram_bot_started_at');
                $this->info('Процесс бота завершен (PID: ' . $processId . ')');
            } catch (\Exception $e) {
                $this->warn('Не удалось завершить процесс: ' . $e->getMessage());
            }
        }
        
        Cache::forget('telegram_bot_last_heartbeat');
        
        $this->info('Бот остановлен');
        return 0;
    }

    private function showStatus(): int
    {
        $botActive = Cache::get('telegram_bot_active', false);
        $processId = Cache::get('telegram_bot_process_id');
        $startedAt = Cache::get('telegram_bot_started_at');
        $lastHeartbeat = Cache::get('telegram_bot_last_heartbeat');
        
        $this->line('=== Состояние Telegram бота ===');
        
        if ($botActive) {
            $this->line('<fg=green>✓</> Статус: Активен');
            
            if ($processId) {
                $this->line("   PID процесса: {$processId}");
                
                // Проверяем существование процесса
                $processExists = $this->checkProcessExists($processId);
                if ($processExists) {
                    $this->line('<fg=green>✓</> Процесс запущен');
                } else {
                    $this->line('<fg=red>✗</> Процесс не найден');
                }
            }
            
            if ($startedAt) {
                $this->line('   Запущен: ' . $startedAt->format('d.m.Y H:i:s') . ' (' . $startedAt->diffForHumans() . ')');
            }
            
            if ($lastHeartbeat) {
                $this->line('Последний ответ: ' . $lastHeartbeat->format('d.m.Y H:i:s') . ' (' . $lastHeartbeat->diffForHumans() . ')');
                
                $minutesSinceHeartbeat = now()->diffInMinutes($lastHeartbeat);
                if ($minutesSinceHeartbeat > 2) {
                    $this->line('<fg=yellow>⚠</> Предупреждение: Процесс не отвечает более 2 минут');
                }
            } else {
                $this->line('<fg=yellow>⚠</> Нет данных о последнем ответе');
            }
        } else {
            $this->line('<fg=red>✗</> Статус: Неактивен');
        }
        
        return 0;
    }
    
    private function checkProcessExists($pid): bool
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $output = shell_exec("tasklist /FI \"PID eq {$pid}\" 2>NUL");
                return $output && strpos($output, (string)$pid) !== false;
            } else {
                return file_exists("/proc/{$pid}");
            }
        } catch (\Exception $e) {
            return false;
        }
    }
} 