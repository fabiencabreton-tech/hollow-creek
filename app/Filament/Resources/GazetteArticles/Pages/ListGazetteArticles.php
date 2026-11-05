<?php

namespace App\Filament\Resources\GazetteArticles\Pages;

use App\Filament\Resources\GazetteArticles\GazetteArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGazetteArticles extends ListRecords
{
    protected static string $resource = GazetteArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
