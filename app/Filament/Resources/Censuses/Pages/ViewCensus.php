<?php

namespace App\Filament\Resources\Censuses\Pages;

use App\Filament\Resources\Censuses\CensusResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCensus extends ViewRecord
{
    protected static string $resource = CensusResource::class;

    protected function getHeaderActions(): array
    {
        return CensusResource::closingActions();
    }
}
