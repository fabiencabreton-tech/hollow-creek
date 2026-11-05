<?php

namespace App\Filament\Resources\Censuses\Pages;

use App\Filament\Resources\Censuses\CensusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCensuses extends ListRecords
{
    protected static string $resource = CensusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Lancer un recensement'),
        ];
    }
}
