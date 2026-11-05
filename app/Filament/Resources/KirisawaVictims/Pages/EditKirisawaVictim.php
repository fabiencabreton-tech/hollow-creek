<?php

namespace App\Filament\Resources\KirisawaVictims\Pages;

use App\Enums\VictimStatus;
use App\Filament\Resources\KirisawaVictims\KirisawaVictimResource;
use App\Models\KirisawaVictim;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKirisawaVictim extends EditRecord
{
    protected static string $resource = KirisawaVictimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Une victime incarnée ou en examen ne disparaît pas sous les pieds de son joueur.
            DeleteAction::make()->visible(fn (KirisawaVictim $record): bool => ! in_array($record->status, [VictimStatus::Reserved, VictimStatus::Played], true)),
        ];
    }
}
