<?php

namespace App\Filament\Resources\ChatMessageResource\Pages;

use App\Filament\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Services\ChatService;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;

class UsersOverviewPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = ChatMessageResource::class;
    protected static string $view = 'filament.pages.users-overview';
    protected static ?string $navigationLabel = 'Обзор пользователей';
    protected static ?string $title = 'Чаты пользователей';

    public function table(Table $table): Table
    {
        $chatService = app(ChatService::class);
        $usersWithChats = $chatService->getUsersWithChats();

        return $table
            ->query(
                \App\Models\User::query()
                    ->whereIn('id', $usersWithChats->pluck('user_id'))
                    ->withCount(['chatMessages'])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Имя пользователя')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => 
                        $record->telegram_data['username'] ?? 'Нет Telegram username'
                    ),

                TextColumn::make('telegram_data')
                    ->label('Telegram ID')
                    ->formatStateUsing(fn ($record) => 
                        $record->telegram_data['id'] ?? 'Не задан'
                    )
                    ->copyable()
                    ->copyMessage('Telegram ID скопирован'),

                TextColumn::make('chat_messages_count')
                    ->label('Всего сообщений')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('user_messages_count')
                    ->label('Вопросов')
                    ->getStateUsing(fn ($record) => 
                        ChatMessage::where('user_id', $record->id)
                            ->where('type', 'user')
                            ->count()
                    )
                    ->badge()
                    ->color('info'),

                TextColumn::make('bot_messages_count')
                    ->label('Ответов бота')
                    ->getStateUsing(fn ($record) => 
                        ChatMessage::where('user_id', $record->id)
                            ->where('type', 'bot')
                            ->count()
                    )
                    ->badge()
                    ->color('success'),

                TextColumn::make('last_activity')
                    ->label('Последняя активность')
                    ->getStateUsing(fn ($record) => 
                        ChatMessage::where('user_id', $record->id)
                            ->latest()
                            ->first()?->created_at
                    )
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->placeholder('Нет сообщений'),

                TextColumn::make('registration_source')
                    ->label('Источник')
                    ->getStateUsing(fn ($record) => 
                        isset($record->telegram_data['id']) ? 'Telegram' : 'Веб'
                    )
                    ->badge()
                    ->color(fn ($record) => 
                        isset($record->telegram_data['id']) ? 'success' : 'primary'
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('view_chat')
                    ->label('Открыть чат')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->color('primary')
                    ->url(fn ($record) => static::getResource()::getUrl('user-chat', ['user' => $record->id])),

                Action::make('clear_chat')
                    ->label('Очистить чат')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => "Очистить чат пользователя {$record->name}")
                    ->modalDescription('Вы уверены, что хотите удалить всю историю чата этого пользователя? Это действие необратимо.')
                    ->action(function ($record) {
                        $chatService = app(ChatService::class);
                        if ($chatService->clearUserChatHistory($record->id)) {
                            \Filament\Notifications\Notification::make()
                                ->title("История чата пользователя {$record->name} успешно очищена")
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Ошибка при очистке истории чата')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->filters([
                // Можно добавить фильтры по типу регистрации, активности и т.д.
            ])
            ->emptyStateHeading('Нет пользователей с сообщениями')
            ->emptyStateDescription('Сообщения появятся здесь после того, как пользователи начнут общаться с ботом.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Обновить')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => redirect(request()->header('Referer'))),
        ];
    }
} 