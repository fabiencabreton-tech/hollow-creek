<?php

namespace App\Filament\Resources\MunicipalRecords\Pages;

use App\Filament\Resources\MunicipalRecords\MunicipalRecordResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditMunicipalRecord extends EditRecord
{
    protected static string $resource = MunicipalRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voir')
                ->label('Voir sur le site')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => MunicipalRecordResource::publicUrl($this->getRecord()))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
