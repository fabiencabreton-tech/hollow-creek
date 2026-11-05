<?php

namespace App\Filament\Resources\Families\Pages;

use App\Filament\Resources\Families\FamilyResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditFamily extends EditRecord
{
    protected static string $resource = FamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voir')
                ->label('Voir sur le site')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => FamilyResource::publicUrl($this->getRecord()))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
