<?php

namespace App\Filament\Resources\Locations\Pages;

use App\Filament\Resources\Locations\LocationResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voir')
                ->label('Voir sur le site')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => LocationResource::publicUrl($this->getRecord()))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
