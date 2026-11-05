<?php

namespace App\Filament\Resources\GazetteArticles\Pages;

use App\Filament\Resources\GazetteArticles\GazetteArticleResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditGazetteArticle extends EditRecord
{
    protected static string $resource = GazetteArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voir')
                ->label('Voir sur le site')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => GazetteArticleResource::publicUrl($this->getRecord()))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
