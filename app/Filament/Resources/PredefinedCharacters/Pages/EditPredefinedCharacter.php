<?php

namespace App\Filament\Resources\PredefinedCharacters\Pages;

use App\Filament\Resources\PredefinedCharacters\PredefinedCharacterResource;
use App\Models\PredefinedCharacter;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPredefinedCharacter extends EditRecord
{
    protected static string $resource = PredefinedCharacterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voir')
                ->label('Voir sur le site')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => PredefinedCharacterResource::publicUrl($this->getRecord()))
                ->openUrlInNewTab(),
            // Un rôle en examen ou déjà incarné ne peut pas disparaître sous les pieds de son joueur.
            DeleteAction::make()
                ->visible(fn (PredefinedCharacter $record): bool => $record->isOpen()),
        ];
    }
}
