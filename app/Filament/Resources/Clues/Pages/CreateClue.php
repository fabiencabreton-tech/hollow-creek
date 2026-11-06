<?php

namespace App\Filament\Resources\Clues\Pages;

use App\Filament\Resources\Clues\ClueResource;
use App\Models\Clue;
use App\Services\ClueService;
use Filament\Resources\Pages\CreateRecord;

class CreateClue extends CreateRecord
{
    protected static string $resource = ClueResource::class;

    protected function afterCreate(): void
    {
        /** @var Clue $clue */
        $clue = $this->getRecord();
        app(ClueService::class)->notifyHolders($clue, $clue->characters()->with('user')->get());
    }
}
