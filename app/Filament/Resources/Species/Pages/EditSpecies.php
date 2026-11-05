<?php

namespace App\Filament\Resources\Species\Pages;

use App\Filament\Resources\Species\SpeciesResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSpecies extends EditRecord
{
    protected static string $resource = SpeciesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voir')
                ->label('Voir sur le site')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => SpeciesResource::publicUrl($this->getRecord()))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
