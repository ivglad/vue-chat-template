<?php

namespace App\Filament\Widgets;

use App\Models\ChatMessage;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class ChatWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    // Интервал автообновления виджета (в секундах)
    protected static ?string $pollingInterval = '10s';
    
    public function getBotStatus(): array
    {
        $botActive = Cache::get('telegram_bot_active', false);
        $processId = Cache::get('telegram_bot_process_id');
        $lastHeartbeat = Cache::get('telegram_bot_last_heartbeat');
        $startedAt = Cache::get('telegram_bot_started_at');
        
        // Проверяем heartbeat (должен быть не старше 2 минут)
        $heartbeatOk = $lastHeartbeat && now()->diffInMinutes($lastHeartbeat) < 2;
        
        // Проверяем, действительно ли процесс запущен
        $processExists = false;
        if ($processId) {
            $processExists = $this->checkProcessExists($processId);
        }
        
        // Определяем реальное состояние
        $reallyActive = $botActive && $processExists && $heartbeatOk;
        
        if ($botActive && (!$processExists || !$heartbeatOk)) {
            // Процесс должен быть активен, но не отвечает - обновляем статус
            Cache::put('telegram_bot_active', false);
            Cache::forget('telegram_bot_process_id');
            Cache::forget('telegram_bot_started_at');
            $reallyActive = false;
        }
        
        // Формируем описание
        $description = '• Бот остановлен';
        if ($reallyActive) {
            $uptime = $startedAt ? '• Работает ' . $startedAt->diffForHumans(null, true) : 'Активен';
            $description = $uptime;
        } elseif ($botActive && !$processExists) {
            $description = 'Процесс завершился неожиданно • Требуется перезапуск';
        } elseif ($botActive && !$heartbeatOk) {
            $description = 'Процесс не отвечает (более 2 минут) • Требуется перезапуск';
        }
        
        return [
            'active' => $reallyActive,
            'status' => $reallyActive ? 'Активен' : 'Неактивен',
            'description' => $description,
            'color' => $reallyActive ? 'success' : 'danger',
            'icon' => $reallyActive ? 'heroicon-m-play-circle' : 'heroicon-m-stop-circle'
        ];
    }
    
    private function checkProcessExists($pid): bool
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                // Для Windows
                $output = shell_exec("tasklist /FI \"PID eq {$pid}\" 2>NUL");
                return $output && strpos($output, (string)$pid) !== false;
            } else {
                // Для Linux/Unix систем
                return file_exists("/proc/{$pid}");
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getStats(): array
    {
        // Базовая статистика
        $totalMessages = ChatMessage::count();
        $todayMessages = ChatMessage::whereDate('created_at', today())->count();
        $userMessages = ChatMessage::where('type', 'user')->count();
        $botMessages = ChatMessage::where('type', 'bot')->count();
        
        // Статистика пользователей
        $activeUsers = ChatMessage::distinct('user_id')->count('user_id');
        $todayActiveUsers = ChatMessage::whereDate('created_at', today())
            ->distinct('user_id')
            ->count('user_id');
        
        // Статистика связанных сообщений
        $linkedMessages = ChatMessage::whereNotNull('parent_id')->count();
        $responseRate = $userMessages > 0 ? round(($botMessages / $userMessages) * 100, 1) : 0;
        
        // Telegram пользователи
        $telegramUsers = User::whereNotNull('telegram_data')->count();
        
        // Состояние бота
        $botStatus = $this->getBotStatus();

        return [
            Stat::make('Состояние бота', $botStatus['status'])
                ->description($botStatus['description'])
                ->descriptionIcon($botStatus['icon'])
                ->color($botStatus['color'])
                ->url('/admin/telegram-bot-settings')
                ->extraAttributes([
                    'title' => 'Нажмите для перехода к управлению ботом'
                ]),

            Stat::make('Активных пользователей', $activeUsers)
                ->description($todayActiveUsers > 0 ? "{$todayActiveUsers} активны сегодня" : 'Сегодня активности нет')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->url('/admin/chat-messages/users'),

            Stat::make('Всего сообщений', $totalMessages)
                ->description($todayMessages > 0 ? "{$todayMessages} сегодня" : 'Сегодня сообщений нет')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('success'),

            Stat::make('Вопросов / Ответов', "{$userMessages} / {$botMessages}")
                ->description("Отвечаемость: {$responseRate}%")
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

            Stat::make('Telegram пользователей', $telegramUsers)
                ->description($telegramUsers > 0 ? 'Автоматическая регистрация работает' : 'Нет Telegram пользователей')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('warning'),
        ];
    }
}
