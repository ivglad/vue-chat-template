<?php

namespace App\Filament\Resources\AudioTranscriptionResource\Pages;

use App\Filament\Resources\AudioTranscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAudioTranscriptions extends ListRecords
{
    protected static string $resource = AudioTranscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
