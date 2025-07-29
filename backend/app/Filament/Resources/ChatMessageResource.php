<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatMessageResource\Pages;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatService;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class ChatMessageResource extends Resource
{
    protected static ?string $model = ChatMessage::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Чаты пользователей';
    protected static ?string $modelLabel = 'Сообщение чата';
    protected static ?string $pluralModelLabel = 'Чаты пользователей';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Пользователь')
                    ->relationship('user', 'name')
                    ->required()
                    ->disabled(),

                Forms\Components\Select::make('parent_id')
                    ->label('Родительское сообщение')
                    ->relationship('parent', 'message')
                    ->disabled()
                    ->placeholder('Нет (корневое сообщение)'),

                Textarea::make('message')
                    ->label('Сообщение')
                    ->required()
                    ->rows(3)
                    ->disabled(),

                Forms\Components\Select::make('type')
                    ->label('Тип')
                    ->options([
                        'user' => 'Пользователь',
                        'bot' => 'Бот',
                    ])
                    ->disabled(),

                Forms\Components\Toggle::make('is_processed')
                    ->label('Обработано')
                    ->disabled(),

                Forms\Components\TagsInput::make('context_documents')
                    ->label('Документы-источники')
                    ->disabled()
                    ->placeholder('Нет связанных документов'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->user?->telegram_data['username'] ?? 'Нет username'),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->colors([
                        'primary' => 'user',
                        'success' => 'bot',
                    ])
                    ->icons([
                        'heroicon-o-user' => 'user',
                        'heroicon-o-cpu-chip' => 'bot',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'user' => 'Пользователь',
                        'bot' => 'Бот',
                        default => $state,
                    }),

                TextColumn::make('parent_id')
                    ->label('Связь')
                    ->formatStateUsing(fn ($state, $record) => $state ? 
                        "↳ Ответ на #{$state}" : 
                        ($record->type === 'user' ? '● Вопрос' : '● Сообщение')
                    )
                    ->color(fn ($record) => $record->parent_id ? 'success' : 'primary'),

                // TextColumn::make('message')
                //     ->label('Сообщение')
                //     ->limit(100)
                //     ->searchable()
                //     ->wrap(),

                TextColumn::make('replies_count')
                    ->label('Ответов')
                    ->counts('replies')
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->label('Время')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип сообщения')
                    ->options([
                        'user' => 'Пользователь',
                        'bot' => 'Бот',
                    ]),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Пользователь')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('parent_id')
                    ->label('Тип сообщения')
                    ->trueLabel('Ответы бота')
                    ->falseLabel('Сообщения пользователей')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('parent_id'),
                        false: fn ($query) => $query->whereNull('parent_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('view_user_chat')
                    ->label('Чат пользователя')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->color('primary')
                    ->url(fn ($record) => static::getUrl('user-chat', ['user' => $record->user_id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('users_overview')
                    ->label('Обзор пользователей')
                    ->icon('heroicon-o-users')
                    ->color('primary')
                    ->url(static::getUrl('users-overview')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatMessages::route('/'),
            'chat' => Pages\ChatPage::route('/chat'),
            'users-overview' => Pages\UsersOverviewPage::route('/users'),
            'user-chat' => Pages\UserChatPage::route('/user/{user}/chat'),
        ];
    }

    public static function getNavigationItems(): array
    {
        return [
            ...parent::getNavigationItems(),
            \Filament\Navigation\NavigationItem::make('Мой чат')
                ->url(static::getUrl('chat'))
                ->icon('heroicon-o-chat-bubble-oval-left')
                ->group(static::getNavigationGroup())
                ->sort(0),
            \Filament\Navigation\NavigationItem::make('Обзор пользователей')
                ->url(static::getUrl('users-overview'))
                ->icon('heroicon-o-users')
                ->group(static::getNavigationGroup())
                ->sort(1),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $usersWithChats = ChatMessage::distinct('user_id')->count('user_id');
        return $usersWithChats > 0 ? (string) $usersWithChats : null;
    }

    public static function canCreate(): bool
    {
        return false; // Создание через интерфейс чата
    }

    public static function canEdit($record): bool
    {
        return false; // Редактирование запрещено
    }
}
