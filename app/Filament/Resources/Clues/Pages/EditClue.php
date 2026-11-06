<?php

namespace App\Filament\Resources\Clues\Pages;

use App\Filament\Resources\Clues\ClueResource;
use App\Models\Clue;
use App\Services\ClueService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClue extends EditRecord
{
    protected static string $resource = ClueResource::class;

    /** @var list<int> */
    private array $holdersBefore = [];

    protected function beforeSave(): void
    {
        $this->holdersBefore = $this->getRecord()->characters()->pluck('characters.id')->all();
    }

    protected function afterSave(): void
    {
        /** @var Clue $clue */
        $clue = $this->getRecord();
        $added = $clue->characters()->whereNotIn('characters.id', $this->holdersBefore)->with('user')->get();

        if ($added->isNotEmpty()) {
            app(ClueService::class)->notifyHolders($clue, $added);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
