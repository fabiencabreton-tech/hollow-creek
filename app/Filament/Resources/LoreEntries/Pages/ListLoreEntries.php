<?php

namespace App\Filament\Resources\LoreEntries\Pages;

use App\Filament\Resources\LoreEntries\LoreEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoreEntries extends ListRecords
{
    protected static string $resource = LoreEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
