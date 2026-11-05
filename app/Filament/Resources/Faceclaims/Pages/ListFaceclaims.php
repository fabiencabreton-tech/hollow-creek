<?php

namespace App\Filament\Resources\Faceclaims\Pages;

use App\Filament\Resources\Faceclaims\FaceclaimResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFaceclaims extends ListRecords
{
    protected static string $resource = FaceclaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
