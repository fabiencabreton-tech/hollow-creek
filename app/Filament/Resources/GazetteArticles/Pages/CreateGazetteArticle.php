<?php

namespace App\Filament\Resources\GazetteArticles\Pages;

use App\Filament\Resources\GazetteArticles\GazetteArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGazetteArticle extends CreateRecord
{
    protected static string $resource = GazetteArticleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = auth()->id();

        return $data;
    }
}
