<?php

namespace App\Filament\Resources\Characters\Pages;

use App\Filament\Resources\Characters\CharacterResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCharacter extends ViewRecord
{
    protected static string $resource = CharacterResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->fullName();
    }

    protected function getHeaderActions(): array
    {
        return CharacterResource::reviewActions();
    }
}
