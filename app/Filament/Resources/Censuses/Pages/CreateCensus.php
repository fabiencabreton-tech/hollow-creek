<?php

namespace App\Filament\Resources\Censuses\Pages;

use App\Filament\Resources\Censuses\CensusResource;
use App\Models\Census;
use App\Services\CensusService;
use Filament\Resources\Pages\CreateRecord;

class CreateCensus extends CreateRecord
{
    protected static string $resource = CensusResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Census
    {
        $census = new Census($data);
        $census->forceFill(['created_by' => $data['created_by']])->save();

        return $census;
    }

    protected function afterCreate(): void
    {
        /** @var Census $census */
        $census = $this->getRecord();
        app(CensusService::class)->announce($census);
    }

    protected function getRedirectUrl(): string
    {
        return CensusResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
