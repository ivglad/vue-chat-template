<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class SystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static string $view = 'filament.pages.system-settings';
    protected static ?string $title = 'AI-модели';
    protected static ?string $navigationLabel = 'AI-модели';
    protected static ?string $navigationGroup = 'Система';
    protected static ?int $navigationSort = 20;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getEnvData());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Выбор AI-модели')
                    ->description('Выберите основную модель для ответов на вопросы по документам')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Select::make('default_ai_model')
                            ->label('Основная AI-модель')
                            ->options([
                                'yandex' => 'Yandex GPT',
                                'gigachat' => 'GigaChat (Сбер)',
                                'openrouter' => 'OpenRouter',
                            ])
                            ->default('yandex')
                            ->helperText('Эта модель будет использоваться по умолчанию для ответов на вопросы')
                            ->required(),
                    ]),

                Section::make('YandexGPT')
                    ->description('Конфигурация для работы с YandexGPT API')
                    ->icon('heroicon-o-cpu-chip')
                    ->schema([
                        TextInput::make('yandex_gpt_api_key')
                            ->label('API Key')
                            ->placeholder('Введите API ключ YandexGPT')
                            ->password()
                            ->revealable()
                            ->helperText('API ключ для доступа к YandexGPT')
                            ->maxLength(255)
                            ->rules(['nullable', 'string']),

                        TextInput::make('yandex_gpt_folder_id')
                            ->label('Folder ID')
                            ->placeholder('Введите Folder ID (например: b1g...)')
                            ->helperText('Идентификатор папки в Yandex Cloud')
                            ->maxLength(255)
                            ->rules(['nullable', 'string', 'regex:/^[a-z0-9]+$/i']),
                    ])
                    ->columns(2),

                Section::make('GigaChat')
                    ->description('Конфигурация для работы с GigaChat API (Сбер)')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        TextInput::make('gigachat_auth_key')
                            ->label('Auth Key')
                            ->placeholder('Введите Base64 ключ авторизации')
                            ->password()
                            ->revealable()
                            ->helperText('Base64 закодированный ключ: base64_encode("client_id:client_secret")')
                            ->maxLength(500)
                            ->rules(['nullable', 'string']),

                        Select::make('gigachat_scope')
                            ->label('Scope')
                            ->options([
                                'GIGACHAT_API_PERS' => 'GIGACHAT_API_PERS (Персональный)',
                                'GIGACHAT_API_CORP' => 'GIGACHAT_API_CORP (Корпоративный)',
                            ])
                            ->default('GIGACHAT_API_PERS')
                            ->helperText('Область доступа для GigaChat API')
                            ->rules(['nullable', 'string']),
                    ])
                    ->columns(2),

                Section::make('OpenRouter')
                    ->description('Конфигурация для работы с OpenRouter API')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        TextInput::make('openrouter_api_key')
                            ->label('API Key')
                            ->placeholder('Введите API ключ OpenRouter')
                            ->password()
                            ->revealable()
                            ->helperText('API ключ для доступа к OpenRouter (получить на https://openrouter.ai/keys)')
                            ->maxLength(255)
                            ->rules(['nullable', 'string']),

                        Select::make('openrouter_default_model')
                            ->label('Модель по умолчанию')
                            ->options([
                                'qwen/qwen3-coder:free' => 'Qwen3 Coder (Free)',
                                'meta-llama/llama-3.2-3b-instruct:free' => 'Llama 3.2 3B (Free)',
                                'microsoft/phi-3-mini-128k-instruct:free' => 'Phi-3 Mini (Free)',
                                'google/gemma-2-9b-it:free' => 'Gemma 2 9B (Free)',
                                'mistralai/mistral-7b-instruct:free' => 'Mistral 7B (Free)',
                                'huggingfaceh4/zephyr-7b-beta:free' => 'Zephyr 7B Beta (Free)',
                                'openchat/openchat-7b:free' => 'OpenChat 7B (Free)',
                                'gryphe/mythomist-7b:free' => 'MythoMist 7B (Free)',
                                'undi95/toppy-m-7b:free' => 'Toppy M 7B (Free)',
                                'openrouter/auto' => 'Auto (Best Available)',
                            ])
                            ->default('qwen/qwen3-coder:free')
                            ->helperText('Модель, которая будет использоваться по умолчанию для OpenRouter')
                            ->rules(['nullable', 'string']),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testYandexConnection')
                ->label('Тест YandexGPT')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action('testYandexConnection')
                ->visible(fn () => !empty($this->data['yandex_gpt_api_key']) && !empty($this->data['yandex_gpt_folder_id'])),

            Action::make('testGigaChatConnection')
                ->label('Тест GigaChat')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('warning')
                ->action('testGigaChatConnection')
                ->visible(fn () => !empty($this->data['gigachat_auth_key'])),

            Action::make('testOpenRouterConnection')
                ->label('Тест OpenRouter')
                ->icon('heroicon-o-globe-alt')
                ->color('gray')
                ->action('testOpenRouterConnection')
                ->visible(fn () => !empty($this->data['openrouter_api_key'])),

            Action::make('save')
                ->label('Сохранить настройки')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save')
                ->requiresConfirmation()
                ->modalHeading('Сохранить настройки AI-моделей?')
                ->modalDescription('Это обновит файл .env с новыми значениями для AI-моделей.')
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
                ->body('Файл .env успешно обновлен')
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
            'default_ai_model' => env('DEFAULT_AI_MODEL', 'yandex'),
            'yandex_gpt_api_key' => env('YANDEX_GPT_API_KEY'),
            'yandex_gpt_folder_id' => env('YANDEX_GPT_FOLDER_ID'),
            'gigachat_auth_key' => env('GIGACHAT_AUTH_KEY'),
            'gigachat_scope' => env('GIGACHAT_SCOPE', 'GIGACHAT_API_PERS'),
            'openrouter_api_key' => env('OPENROUTER_API_KEY'),
            'openrouter_default_model' => env('OPENROUTER_DEFAULT_MODEL', 'qwen/qwen3-coder:free'),
        ];
    }

    public function testYandexConnection(): void
    {
        $data = $this->form->getState();
        $apiKey = $data['yandex_gpt_api_key'] ?? '';
        $folderId = $data['yandex_gpt_folder_id'] ?? '';

        if (empty($apiKey) || empty($folderId)) {
            Notification::make()
                ->title('Ошибка тестирования')
                ->body('Заполните API Key и Folder ID для YandexGPT')
                ->warning()
                ->send();
            return;
        }

        try {
            // Простой тест подключения к YandexGPT API
            $response = Http::withHeaders([
                'Authorization' => 'Api-Key ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://llm.api.cloud.yandex.net/foundationModels/v1/completion', [
                'modelUri' => "gpt://{$folderId}/yandexgpt-lite",
                'completionOptions' => [
                    'stream' => false,
                    'temperature' => 0.1,
                    'maxTokens' => 10,
                ],
                'messages' => [
                    [
                        'role' => 'user',
                        'text' => 'Тест'
                    ]
                ]
            ]);

            if ($response->successful()) {
                Notification::make()
                    ->title('YandexGPT подключение успешно')
                    ->body('API ключ и Folder ID работают корректно')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Ошибка YandexGPT')
                    ->body('Код ошибки: ' . $response->status() . '. ' . $response->body())
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Ошибка подключения к YandexGPT')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testGigaChatConnection(): void
    {
        $data = $this->form->getState();
        $authKey = $data['gigachat_auth_key'] ?? '';
        $scope = $data['gigachat_scope'] ?? 'GIGACHAT_API_PERS';

        if (empty($authKey)) {
            Notification::make()
                ->title('Ошибка тестирования')
                ->body('Заполните Auth Key для GigaChat')
                ->warning()
                ->send();
            return;
        }

        try {
            // Сначала получаем токен
            $tokenResponse = Http::withHeaders([
                'Authorization' => "Basic {$authKey}",
                'RqUID' => (string) \Illuminate\Support\Str::uuid(),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ])
            ->withoutVerifying()
            ->asForm()
            ->timeout(10)
            ->post('https://ngw.devices.sberbank.ru:9443/api/v2/oauth', [
                'scope' => $scope,
            ]);

            if (!$tokenResponse->successful()) {
                Notification::make()
                    ->title('Ошибка получения токена GigaChat')
                    ->body('Код ошибки: ' . $tokenResponse->status() . '. Проверьте Auth Key и Scope.')
                    ->danger()
                    ->send();
                return;
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'] ?? null;

            if (!$accessToken) {
                Notification::make()
                    ->title('Ошибка GigaChat')
                    ->body('Не удалось получить access_token из ответа')
                    ->danger()
                    ->send();
                return;
            }

            // Тестируем API с полученным токеном
            $chatResponse = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->withoutVerifying()
                ->timeout(10)
                ->post('https://gigachat.devices.sberbank.ru/api/v1/chat/completions', [
                    'model' => 'GigaChat',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Тест'
                        ]
                    ],
                    'stream' => false,
                    'max_tokens' => 10,
                ]);

            if ($chatResponse->successful()) {
                Notification::make()
                    ->title('GigaChat подключение успешно')
                    ->body('Auth Key и Scope работают корректно')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Ошибка GigaChat API')
                    ->body('Код ошибки: ' . $chatResponse->status() . '. ' . $chatResponse->body())
                    ->danger()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Ошибка подключения к GigaChat')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testOpenRouterConnection(): void
    {
        $data = $this->form->getState();
        $apiKey = $data['openrouter_api_key'] ?? '';
        $model = $data['openrouter_default_model'] ?? 'qwen/qwen3-coder:free';

        if (empty($apiKey)) {
            Notification::make()
                ->title('Ошибка тестирования')
                ->body('Заполните API Key для OpenRouter')
                ->warning()
                ->send();
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url', 'http://localhost'),
                'X-Title' => config('app.name', 'Laravel App'),
            ])
            ->timeout(10)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Тест подключения'
                    ]
                ],
                'max_tokens' => 10,
                'temperature' => 0.1,
            ]);

            if ($response->successful()) {
                Notification::make()
                    ->title('OpenRouter подключение успешно')
                    ->body('API ключ работает корректно с моделью: ' . $model)
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Ошибка OpenRouter API')
                    ->body('Код ошибки: ' . $response->status() . '. ' . $response->body())
                    ->danger()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Ошибка подключения к OpenRouter')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            throw new \Exception('Файл .env не найден');
        }

        $envContent = File::get($envPath);

        $updates = [
            'DEFAULT_AI_MODEL' => $data['default_ai_model'] ?? 'yandex',
            'YANDEX_GPT_API_KEY' => $data['yandex_gpt_api_key'] ?? '',
            'YANDEX_GPT_FOLDER_ID' => $data['yandex_gpt_folder_id'] ?? '',
            'GIGACHAT_AUTH_KEY' => $data['gigachat_auth_key'] ?? '',
            'GIGACHAT_SCOPE' => $data['gigachat_scope'] ?? 'GIGACHAT_API_PERS',
            'OPENROUTER_API_KEY' => $data['openrouter_api_key'] ?? '',
            'OPENROUTER_DEFAULT_MODEL' => $data['openrouter_default_model'] ?? 'qwen/qwen3-coder:free',
        ];

        foreach ($updates as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            $replacement = "{$key}=" . ($value ? $value : '');
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                // Если переменная не найдена, добавляем в конец файла
                $envContent .= "\n{$replacement}";
            }
        }

        File::put($envPath, $envContent);

        // Очищаем кеш конфигурации
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}