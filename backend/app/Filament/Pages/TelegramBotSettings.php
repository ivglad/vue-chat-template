<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramBotSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string $view = 'filament.pages.telegram-bot-settings';
    protected static ?string $title = 'Telegram бот';
    protected static ?string $navigationLabel = 'Telegram бот';
    protected static ?string $navigationGroup = 'Система';
    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getEnvData());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('TelegramSettings')
                    ->tabs([
                        Tabs\Tab::make('Settings')
                            ->label('Настройки')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Telegram Bot Настройки')
                                    ->description('Конфигурация для работы с Telegram Bot API')
                                    ->schema([
                                        TextInput::make('telegram_bot_token')
                                            ->label('Bot Token')
                                            ->placeholder('Введите токен Telegram бота (например: 123456789:ABC-DEF...)')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Токен бота, полученный от @BotFather')
                                            ->maxLength(255)
                                            ->rules(['nullable', 'string', 'regex:/^\d+:[A-Za-z0-9_-]+$/']),

                                        TextInput::make('telegram_contact_chat_id')
                                            ->label('Contact Chat ID')
                                            ->placeholder('Введите ID чата для контактов (например: -1001234567890)')
                                            ->helperText('ID чата или канала для отправки заявок (может быть отрицательным)')
                                            ->maxLength(255)
                                            ->rules(['nullable', 'string', 'regex:/^-?\d+$/']),
                                    ]),
                            ]),

                        Tabs\Tab::make('Management')
                            ->label('Управление')
                            ->icon('heroicon-o-play-circle')
                            ->schema([
                                Section::make('Статус бота')
                                    ->description('Текущее состояние Telegram бота')
                                    ->schema([
                                        TextInput::make('bot_status')
                                            ->label('Статус')
                                            ->live()
                                            ->afterStateHydrated(fn ($component) => $component->state($this->getBotStatus()))
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->suffixIcon(fn () => Cache::get('telegram_bot_active', false) ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                            ->suffixIconColor(fn () => Cache::get('telegram_bot_active', false) ? 'success' : 'danger'),

                                        TextInput::make('bot_process_id')
                                            ->label('PID процесса')
                                            ->live()
                                            ->afterStateHydrated(fn ($component) => $component->state($this->getBotProcessId()))
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('bot_started_at')
                                            ->label('Время запуска')
                                            ->live()
                                            ->afterStateHydrated(fn ($component) => $component->state($this->getBotStartedAt()))
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('bot_last_heartbeat')
                                            ->label('Последний ответ')
                                            ->live()
                                            ->afterStateHydrated(fn ($component) => $component->state($this->getBotLastHeartbeat()))
                                            ->disabled()
                                            ->dehydrated(false),
                                    ])
                                    ->columns(2),

                                Section::make('Управление ботом')
                                    ->description('Действия для управления Telegram ботом')
                                    ->schema([
                                        Actions::make([
                                            FormAction::make('toggleBot')
                                                ->label(fn () => Cache::get('telegram_bot_active', false) ? 'Остановить бота' : 'Запустить бота')
                                                ->icon(fn () => Cache::get('telegram_bot_active', false) ? 'heroicon-o-stop-circle' : 'heroicon-o-play-circle')
                                                ->color(fn () => Cache::get('telegram_bot_active', false) ? 'danger' : 'success')
                                                ->action('toggleBotStatus')
                                                ->requiresConfirmation()
                                                ->modalHeading(fn () => Cache::get('telegram_bot_active', false) ? 'Остановить Telegram бота?' : 'Запустить Telegram бота?')
                                                ->modalDescription('Вы уверены, что хотите изменить состояние бота?')
                                                ->modalSubmitActionLabel('Да, продолжить'),

                                            FormAction::make('refreshStatus')
                                                ->label('Обновить статус')
                                                ->icon('heroicon-o-arrow-path')
                                                ->color('gray')
                                                ->action(function () {
                                                    $this->checkBotStatus();
                                                    $this->refreshBotStatusFields();
                                                    Notification::make()
                                                        ->title('Статус обновлен')
                                                        ->success()
                                                        ->send();
                                                }),

                                            FormAction::make('viewLogs')
                                                ->label('Просмотр логов')
                                                ->icon('heroicon-o-document-text')
                                                ->color('info')
                                                ->action('viewLogs'),
                                        ])
                                        ->fullWidth()
                                        ->alignment('center'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testTelegramConnection')
                ->label('Тест подключения')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action('testTelegramConnection')
                ->visible(fn () => !empty($this->data['telegram_bot_token'])),

            Action::make('save')
                ->label('Сохранить настройки')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save')
                ->requiresConfirmation()
                ->modalHeading('Сохранить настройки?')
                ->modalDescription('Это обновит файл .env с новыми значениями токенов.')
                ->modalSubmitActionLabel('Да, сохранить'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            $this->updateEnvFile($data);

            Notification::make()
                ->title('Настройки сохранены')
                ->body('Токены Telegram бота успешно обновлены')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Ошибка сохранения')
                ->body('Не удалось обновить файл .env: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function getEnvData(): array
    {
        return [
            'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN'),
            'telegram_contact_chat_id' => env('TELEGRAM_CONTACT_CHAT_ID'),
        ];
    }

    public function testTelegramConnection(): void
    {
        $data = $this->form->getState();
        $token = $data['telegram_bot_token'] ?? '';

        if (empty($token)) {
            Notification::make()
                ->title('Ошибка тестирования')
                ->body('Заполните Bot Token для Telegram')
                ->warning()
                ->send();
            return;
        }

        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getMe");

            if ($response->successful()) {
                $botInfo = $response->json();
                if ($botInfo['ok']) {
                    $botData = $botInfo['result'];
                    Notification::make()
                        ->title('Подключение успешно')
                        ->body("Бот: @{$botData['username']} ({$botData['first_name']})")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Ошибка подключения')
                        ->body('Неверный токен бота')
                        ->danger()
                        ->send();
                }
            } else {
                Notification::make()
                    ->title('Ошибка подключения')
                    ->body('Код ошибки: ' . $response->status())
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Ошибка подключения')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function toggleBotStatus(): void
    {
        $currentStatus = Cache::get('telegram_bot_active', false);
        $newStatus = !$currentStatus;
        
        Log::info('TelegramBotSettings: Попытка переключения состояния бота', [
            'current_status' => $currentStatus,
            'new_status' => $newStatus,
            'user' => auth()->user()?->name ?? 'unknown'
        ]);
        
        if ($newStatus) {
            try {
                $basePath = base_path();
                $phpPath = PHP_BINARY;
                $command = "cd {$basePath} && nohup {$phpPath} artisan telegram:poll --daemon > storage/logs/telegram_bot.log 2>&1 & echo $!";
                
                Log::info('TelegramBotSettings: Выполняем команду запуска', ['command' => $command]);
                
                $pid = trim(shell_exec($command));
                
                if ($pid && is_numeric($pid)) {
                    Cache::put('telegram_bot_active', true);
                    Cache::put('telegram_bot_process_id', (int)$pid);
                    Cache::put('telegram_bot_started_at', now());
                    
                    $this->refreshBotStatusFields();
                    
                    Notification::make()
                        ->title('Бот запущен')
                        ->body('Telegram бот успешно запущен (PID: ' . $pid . ')')
                        ->success()
                        ->send();
                } else {
                    throw new \Exception('Не удалось запустить процесс. PID: ' . $pid);
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Ошибка запуска')
                    ->body('Не удалось запустить бота: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        } else {
            try {
                Cache::put('telegram_bot_active', false);
                
                $processId = Cache::get('telegram_bot_process_id');
                if ($processId) {
                    if (PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Darwin') {
                        exec("kill {$processId} 2>/dev/null");
                    } elseif (PHP_OS_FAMILY === 'Windows') {
                        exec("taskkill /PID {$processId} /F 2>NUL");
                    }
                    
                    Cache::forget('telegram_bot_process_id');
                    Cache::forget('telegram_bot_started_at');
                }
                
                $this->refreshBotStatusFields();
                
                Notification::make()
                    ->title('Бот остановлен')
                    ->body('Telegram бот успешно остановлен')
                    ->warning()
                    ->send();
                    
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Ошибка остановки')
                    ->body('Ошибка при остановке бота: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }

    public function checkBotStatus(): array
    {
        $botActive = Cache::get('telegram_bot_active', false);
        $processId = Cache::get('telegram_bot_process_id');
        $lastHeartbeat = Cache::get('telegram_bot_last_heartbeat');
        $startedAt = Cache::get('telegram_bot_started_at');
        
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

    public function viewLogs(): void
    {
        try {
            $logPath = storage_path('logs/telegram_bot.log');
            if (file_exists($logPath)) {
                $logs = $this->tail($logPath, 20);
                Notification::make()
                    ->title('Последние логи бота')
                    ->body(implode("\n", $logs))
                    ->info()
                    ->persistent()
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
    }

    private function tail(string $file, int $lines = 20): array
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

    public function getBotStatus(): string
    {
        return Cache::get('telegram_bot_active', false) ? 'Активен' : 'Неактивен';
    }

    public function getBotProcessId(): string
    {
        $processId = Cache::get('telegram_bot_process_id');
        if (!$processId) {
            return 'Не запущен';
        }

        // Проверяем, существует ли процесс
        $processExists = false;
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec("tasklist /FI \"PID eq {$processId}\" 2>NUL");
            $processExists = $output && strpos($output, (string)$processId) !== false;
        } else {
            $processExists = file_exists("/proc/{$processId}");
        }

        return $processId . ($processExists ? ' ✓' : ' ✗');
    }

    public function getBotStartedAt(): string
    {
        $startedAt = Cache::get('telegram_bot_started_at');
        if (!$startedAt) {
            return 'Не запущен';
        }

        return $startedAt->format('d.m.Y H:i:s') . ' (' . $startedAt->diffForHumans() . ')';
    }

    public function getBotLastHeartbeat(): string
    {
        $lastHeartbeat = Cache::get('telegram_bot_last_heartbeat');
        if (!$lastHeartbeat) {
            return 'Нет данных';
        }

        return $lastHeartbeat->format('d.m.Y H:i:s') . ' (' . $lastHeartbeat->diffForHumans() . ')';
    }

    public function refreshBotStatusFields(): void
    {
        // Обновляем данные формы с актуальными значениями статуса
        $this->data['bot_status'] = $this->getBotStatus();
        $this->data['bot_process_id'] = $this->getBotProcessId();
        $this->data['bot_started_at'] = $this->getBotStartedAt();
        $this->data['bot_last_heartbeat'] = $this->getBotLastHeartbeat();
        
        // Принудительно обновляем форму
        $this->form->fill($this->data);
    }

    private function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            throw new \Exception('Файл .env не найден');
        }

        $envContent = File::get($envPath);

        $updates = [
            'TELEGRAM_BOT_TOKEN' => $data['telegram_bot_token'] ?? '',
            'TELEGRAM_CONTACT_CHAT_ID' => $data['telegram_contact_chat_id'] ?? '',
        ];

        foreach ($updates as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            $replacement = "{$key}=" . ($value ? $value : '');
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        File::put($envPath, $envContent);

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}