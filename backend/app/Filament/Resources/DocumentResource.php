<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Документы';
    protected static ?string $modelLabel = 'Документ';
    protected static ?string $pluralModelLabel = 'Документы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                TextInput::make('google_docs_url')
                    ->label('Ссылка на Google Docs')
                    ->url()
                    ->maxLength(255),

                Select::make('user_id')
                    ->label('Пользователь')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                FileUpload::make('file_path')
                    ->label('Файл документа')
                    ->disk('local')
                    ->directory('documents')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'text/plain', // MIME-тип для .txt
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
                        'application/msword', // .doc
                    ])
                    ->rules([
                        'file',
                        'mimetypes:application/pdf,text/plain,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword',
                    ])
                    ->maxSize(20240) // 20MB
                    ->afterStateUpdated(function ($state, callable $set, DocumentService $documentService) {
                        if ($state) {
                            $extension = pathinfo($state->getClientOriginalName(), PATHINFO_EXTENSION);
                            $set('file_type', $extension);

                            $path = $state->store('temp', 'local');

                            if (!$path || !\Storage::disk('local')->exists($path)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Ошибка')
                                    ->body('Не удалось сохранить временный файл.')
                                    ->danger()
                                    ->send();
                                return;
                            }
            
                            try {
                                // Извлекаем текст из файла
                                $content = $documentService->extractTextFromFile(\Storage::disk('local')->path($path));

                                if ($content) {
                                    // Устанавливаем извлеченный текст в поле content
                                    $set('content', $content);
                                    \Filament\Notifications\Notification::make()
                                        ->title('Успех')
                                        ->body('Текст успешно извлечен из файла.')
                                        ->success()
                                        ->send();
                                } else {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Ошибка')
                                        ->body('Не удалось извлечь текст из файла.')
                                        ->danger()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                // Логируем ошибку и уведомляем пользователя
                                \Illuminate\Support\Facades\Log::error("Failed to extract text from file {$path}: " . $e->getMessage());
                                \Filament\Notifications\Notification::make()
                                    ->title('Ошибка')
                                    ->body('Ошибка при извлечении текста: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            } finally {
                                // Удаляем временный файл
                                if ($path && \Storage::disk('local')->exists($path)) {
                                    \Storage::disk('local')->delete($path);
                                }
                            }
                        }
                    }),

                Textarea::make('content')
                    ->label('Содержимое документа')
                    ->rows(10)
                    ->columnSpanFull()
                    ->helperText('Заполните содержимое вручную или загрузите файл'),

                TextInput::make('file_type')
                    ->label('Тип файла')
                    ->disabled(),

                Toggle::make('embeddings_generated')
                    ->label('Эмбеддинги сгенерированы')
                    ->disabled(),

                Select::make('processing_status')
                    ->label('Статус обработки')
                    ->options([
                        'idle' => 'Ожидание',
                        'processing' => 'Обработка',
                        'completed' => 'Завершено',
                        'failed' => 'Ошибка',
                    ])
                    ->disabled(),

                Select::make('roles')
                    ->label('Роли с доступом')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s') // Автообновление каждые 5 секунд
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('file_type')
                    ->label('Тип файла')
                    ->badge(),

                Tables\Columns\TextColumn::make('processing_status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'idle' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'idle' => 'Ожидание',
                        'processing' => 'Обработка',
                        'completed' => 'Завершено',
                        'failed' => 'Ошибка',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Пользователь')
                    ->relationship('user', 'name'),

                Tables\Filters\SelectFilter::make('processing_status')
                    ->label('Статус обработки')
                    ->options([
                        'idle' => 'Ожидание',
                        'processing' => 'Обработка',
                        'completed' => 'Завершено',
                        'failed' => 'Ошибка',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Action::make('generate_embeddings')
                    ->label(fn (Document $record) => match ($record->processing_status) {
                        'processing' => 'Обработка...',
                        default => 'Генерировать эмбеддинги'
                    })
                    ->icon(fn (Document $record) => match ($record->processing_status) {
                        'processing' => 'heroicon-o-arrow-path',
                        default => 'heroicon-o-cpu-chip'
                    })
                    ->color(fn (Document $record) => match ($record->processing_status) {
                        'processing' => 'warning',
                        default => 'success'
                    })
                    ->visible(fn (Document $record) => in_array($record->processing_status, ['idle', 'failed', 'processing']))
                    ->disabled(fn (Document $record) => $record->processing_status === 'processing')
                    ->action(function (Document $record) {
                        $documentService = app(DocumentService::class);
                        
                        if ($documentService->generateEmbeddingsForDocument($record)) {
                            Notification::make()
                                ->title('Генерация эмбеддингов запущена')
                                ->body('Процесс генерации эмбеддингов начат. Обновите страницу через некоторое время.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Ошибка при запуске генерации эмбеддингов')
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('regenerate_embeddings')
                    ->label('Пересоздать эмбеддинги')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Document $record) => $record->processing_status === 'completed')
                    ->requiresConfirmation()
                    ->action(function (Document $record) {
                        $documentService = app(DocumentService::class);
                        
                        if ($documentService->generateEmbeddingsForDocument($record)) {
                            Notification::make()
                                ->title('Пересоздание эмбеддингов запущено')
                                ->body('Процесс пересоздания эмбеддингов начат. Обновите страницу через некоторое время.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Ошибка при пересоздании эмбеддингов')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Action::make('bulk_generate_embeddings')
                        ->label('Генерировать эмбеддинги')
                        ->icon('heroicon-o-cpu-chip')
                        ->color('success')
                        ->action(function ($records) {
                            $documentService = app(DocumentService::class);
                            $processed = 0;
                            
                            foreach ($records as $record) {
                                if ($documentService->generateEmbeddingsForDocument($record)) {
                                    $processed++;
                                }
                            }
                            
                            Notification::make()
                                ->title("Обработано документов: {$processed} из " . count($records))
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocument::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}