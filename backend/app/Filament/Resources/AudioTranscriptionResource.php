<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AudioTranscriptionResource\Pages;
use App\Models\AudioTranscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Codewithkyrian\Whisper\Whisper;
use function Codewithkyrian\Whisper\readAudio;
use function Codewithkyrian\Whisper\toTimestamp;

use Filament\Notifications\Notification;

class AudioTranscriptionResource extends Resource
{
    protected static ?string $model = AudioTranscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-microphone';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('audio_file')
                    ->label('Аудиофайл')
                    ->acceptedFileTypes(['audio/mpeg', 'audio/wav', 'audio/mp3', 'audio/x-wav'])
                    ->required()
                    ->disk('public')
                    ->directory('audio-uploads')
                    ->visibility('public'),
                Forms\Components\Textarea::make('transcription')
                    ->label('Транскрипция')
                    ->disabled()
                    ->rows(10),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID'),
                Tables\Columns\TextColumn::make('audio_file')->label('Файл'),
                Tables\Columns\TextColumn::make('transcription')->label('Транскрипция')->limit(50),
                Tables\Columns\TextColumn::make('created_at')->label('Создано'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('transcribe')
                    ->label('Транскрибировать')
                    ->action(function (AudioTranscription $record) {
                        try {
                            // Путь к загруженному файлу
                            $filePath = Storage::disk('public')->path($record->audio_file);
                            
                            // Инициализация Whisper с моделью tiny
                            $whisper = Whisper::fromPretrained('medium', baseDir: storage_path('app/models'));
                            
                            // Чтение аудиофайла
                            $audio = readAudio($filePath);
                            
                            // Транскрипция с 4 потоками
                            $segments = $whisper->transcribe($audio, 4);
                            
                            // Форматирование результата с таймкодами
                            $transcription = '';
                            foreach ($segments as $segment) {
                                $transcription .= toTimestamp($segment->startTimestamp) . ': ' . $segment->text . "\n";
                            }

                            // Сохранение результата
                            $record->update([
                                'transcription' => $transcription
                            ]);

                            Notification::make()
                                ->title('Транскрипция завершена')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Ошибка транскрипции')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->icon('heroicon-o-play'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAudioTranscriptions::route('/'),
            'create' => Pages\CreateAudioTranscription::route('/create'),
            'edit' => Pages\EditAudioTranscription::route('/{record}/edit'),
        ];
    }
}