<?php

namespace App\Filament\Resources\ClassifiedAds\Pages;

use App\Filament\Resources\ClassifiedAds\ClassifiedAdResource;
use Filament\Resources\Pages\ListRecords;

class ListClassifiedAds extends ListRecords
{
    protected static string $resource = ClassifiedAdResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
