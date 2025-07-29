<?php

namespace App\Filament\Resources\AudioTranscriptionResource\Pages;

use App\Filament\Resources\AudioTranscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAudioTranscription extends EditRecord
{
    protected static string $resource = AudioTranscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
