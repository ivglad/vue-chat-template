<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactRequestResource\Pages;
use App\Filament\Resources\ContactRequestResource\RelationManagers;
use App\Models\ContactRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactRequestResource extends Resource
{
    protected static ?string $model = ContactRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';
    
    protected static ?string $navigationLabel = 'Заявки с сайта';
    
    protected static ?string $modelLabel = 'Заявка';
    
    protected static ?string $pluralModelLabel = 'Заявки';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('phone')
                    ->label('Телефон')
                    ->required()
                    ->maxLength(20),
                    
                Forms\Components\Textarea::make('comment')
                    ->label('Комментарий')
                    ->maxLength(1000)
                    ->rows(3),
                    
                Forms\Components\TextInput::make('ip_address')
                    ->label('IP адрес')
                    ->disabled(),
                    
                Forms\Components\TextInput::make('user_agent')
                    ->label('User Agent')
                    ->disabled()
                    ->columnSpanFull(),
                    
                Forms\Components\Toggle::make('is_sent_to_telegram')
                    ->label('Отправлено в Telegram')
                    ->disabled(),
                    
                Forms\Components\DateTimePicker::make('sent_to_telegram_at')
                    ->label('Время отправки в Telegram')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('comment')
                    ->label('Комментарий')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (ContactRequest $record): ?string {
                        return $record->comment;
                    }),
                    
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('is_sent_to_telegram')
                    ->label('Отправлено в TG')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('sent_to_telegram_at')
                    ->label('Отправлено в TG')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_sent_to_telegram')
                    ->label('Отправлено в Telegram')
                    ->boolean(),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Дата от'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Дата до'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactRequests::route('/'),
            'create' => Pages\CreateContactRequest::route('/create'),
            'edit' => Pages\EditContactRequest::route('/{record}/edit'),
        ];
    }
}
