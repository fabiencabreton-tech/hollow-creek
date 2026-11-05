<?php

namespace App\Filament\Resources\LoreEntries\Pages;

use App\Filament\Resources\LoreEntries\LoreEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoreEntry extends EditRecord
{
    protected static string $resource = LoreEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
