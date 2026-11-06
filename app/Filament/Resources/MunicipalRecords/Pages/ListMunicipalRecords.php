<?php

namespace App\Filament\Resources\MunicipalRecords\Pages;

use App\Filament\Resources\MunicipalRecords\MunicipalRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMunicipalRecords extends ListRecords
{
    protected static string $resource = MunicipalRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
