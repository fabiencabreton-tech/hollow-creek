<?php

namespace App\Filament\Resources\Districts\Pages;

use App\Filament\Resources\Districts\DistrictResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditDistrict extends EditRecord
{
    protected static string $resource = DistrictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voir')
                ->label('Voir sur le site')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => DistrictResource::publicUrl($this->getRecord()))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
