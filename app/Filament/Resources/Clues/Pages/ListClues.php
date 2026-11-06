<?php

namespace App\Filament\Resources\Clues\Pages;

use App\Filament\Resources\Clues\ClueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClues extends ListRecords
{
    protected static string $resource = ClueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
