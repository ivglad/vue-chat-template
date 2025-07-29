<?php

namespace App\Filament\Resources\ChatMessageResource\Pages;

use App\Filament\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\Document;
use App\Models\User;
use App\Services\ChatService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class UserChatPage extends Page implements HasForms
{
    use InteractsWithForms, InteractsWithFormActions;

    protected static string $resource = ChatMessageResource::class;
    protected static string $view = 'filament.pages.user-chat';
    protected static ?string $title = 'Чат пользователя';

    public ?array $data = [];
    public $chatHistory = [];
    public $user;
    public $isProcessing = false;

    public function mount($user): void
    {
        // Получаем пользователя по ID
        $this->user = User::findOrFail($user);
        
        $this->form->fill();
        $this->loadChatHistory();
    }

    public function getTitle(): string
    {
        return "Чат: {$this->user->name}";
    }

    public function form(Form $form): Form
    {
        // Получаем документы, доступные пользователю
        $availableDocuments = auth()->user()->availableDocuments();

        return $form
            ->schema([
                Select::make('selected_documents')
                    ->label('Выбор контекста')
                    ->placeholder('Выберите документы')
                    ->multiple()
                    ->options($availableDocuments)
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                    
                Textarea::make('message')
                    ->label('Отправить сообщение')
                    ->placeholder('Введите сообщение...')
                    ->required()
                    ->rows(3)
                    ->autofocus()
                    ->disabled($this->isProcessing)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function sendMessage(): void
    {
        if ($this->isProcessing) {
            return;
        }

        try {
            $this->form->validate();

            $message = $this->data['message'];
            $selectedDocuments = $this->data['selected_documents'] ?? [];
            $adminUser = Auth::user();

            if (!$adminUser) {
                throw new \Exception('Администратор не авторизован');
            }

            // Устанавливаем флаг обработки
            $this->isProcessing = true;

            // Очищаем поле сообщения сразу после отправки
            $this->data['message'] = '';
            $this->form->fill($this->data);

            // Обновляем историю чата, чтобы показать сообщение пользователя
            $this->loadChatHistory();

            // Запускаем асинхронную обработку
            $this->dispatch('message-processing');

            // Обрабатываем сообщение через ChatService от лица выбранного пользователя с выбранными документами
            $chatService = app(ChatService::class);
            $response = $chatService->processMessageWithDocuments($this->user, $message, $selectedDocuments);

            // Снимаем флаг обработки
            $this->isProcessing = false;

            if ($response) {
                Notification::make()
                    ->title('Ответ получен')
                    ->success()
                    ->send();

                // Обновляем историю чата с новым ответом
                $this->loadChatHistory();
                
                // Отправляем событие о завершении обработки
                $this->dispatch('message-processed');
            } else {
                Notification::make()
                    ->title('Ошибка при обработке сообщения')
                    ->danger()
                    ->send();
            }

        } catch (Halt $exception) {
            $this->isProcessing = false;
            return;
        } catch (\Exception $e) {
            $this->isProcessing = false;
            Notification::make()
                ->title('Произошла ошибка: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function clearUserChat(): void
    {
        $chatService = app(ChatService::class);
        
        if ($chatService->clearUserChatHistory($this->user->id)) {
            Notification::make()
                ->title("История чата пользователя {$this->user->name} очищена")
                ->success()
                ->send();
            
            $this->loadChatHistory();
        } else {
            Notification::make()
                ->title('Ошибка при очистке чата')
                ->danger()
                ->send();
        }
    }

    #[On('refresh-chat')]
    public function refreshChat(): void
    {
        $this->loadChatHistory();
    }

    private function loadChatHistory(): void
    {
        $chatService = app(ChatService::class);
        $messages = $chatService->getUserChatHistory($this->user->id, 100);
        
        $this->chatHistory = $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'user_name' => $message->user->name ?? 'Неизвестный',
                'message' => $message->message,
                'type' => $message->type,
                'parent_id' => $message->parent_id,
                'replies_count' => $message->replies->count(),
                'context_documents' => $message->context_documents,
                'created_at' => $message->created_at->format('d.m.Y H:i'),
                'created_at_human' => $message->created_at->diffForHumans(),
                'is_reply' => !is_null($message->parent_id),
            ];
        })->toArray();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSendAction(),
            $this->getClearAction(),
        ];
    }

    protected function getSendAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('send')
            ->label($this->isProcessing ? 'Обрабатывается...' : 'Отправить')
            ->icon($this->isProcessing ? 'heroicon-o-arrow-path' : 'heroicon-o-paper-airplane')
            ->color('primary')
            ->disabled($this->isProcessing)
            ->action('sendMessage');
    }

    protected function getClearAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('clear')
            ->label('Очистить чат')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->disabled($this->isProcessing)
            ->requiresConfirmation()
            ->modalHeading("Очистить чат пользователя {$this->user->name}")
            ->modalDescription('Вы уверены, что хотите удалить всю историю чата этого пользователя? Это действие необратимо.')
            ->action('clearUserChat');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back_to_overview')
                ->label('К обзору пользователей')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(static::getResource()::getUrl('users-overview')),

            \Filament\Actions\Action::make('user_info')
                ->label('Информация о пользователе')
                ->icon('heroicon-o-user')
                ->color('info')
                ->modalContent(function () {
                    $telegramData = $this->user->telegram_data ?? [];
                    $documentsCount = $this->user->documents()->count();
                    $processedDocs = $this->user->documents()->where('embeddings_generated', true)->count();
                    
                    return view('filament.modals.user-info', [
                        'user' => $this->user,
                        'telegramData' => $telegramData,
                        'documentsCount' => $documentsCount,
                        'processedDocs' => $processedDocs,
                    ]);
                })
                ->modalWidth('lg'),
        ];
    }
} 