<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
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
            'yandex_gpt_api_key' => env('YANDEX_GPT_API_KEY'),
            'yandex_gpt_folder_id' => env('YANDEX_GPT_FOLDER_ID'),
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



    private function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            throw new \Exception('Файл .env не найден');
        }

        $envContent = File::get($envPath);

        $updates = [
            'YANDEX_GPT_API_KEY' => $data['yandex_gpt_api_key'] ?? '',
            'YANDEX_GPT_FOLDER_ID' => $data['yandex_gpt_folder_id'] ?? '',
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