<?php

namespace App\Filament\Resources\PredefinedCharacters\Pages;

use App\Filament\Resources\PredefinedCharacters\PredefinedCharacterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPredefinedCharacters extends ListRecords
{
    protected static string $resource = PredefinedCharacterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
