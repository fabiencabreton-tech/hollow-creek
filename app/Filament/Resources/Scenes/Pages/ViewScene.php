<?php

namespace App\Filament\Resources\Scenes\Pages;

use App\Filament\Resources\Scenes\SceneResource;
use App\Models\Scene;
use App\Support\ActivityLogger;
use Filament\Resources\Pages\ViewRecord;

class ViewScene extends ViewRecord
{
    protected static string $resource = SceneResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var Scene $scene */
        $scene = $this->getRecord();

        // Lire une scène privée depuis l'administration laisse une trace.
        if ($scene->isPrivate()) {
            ActivityLogger::record('scene.consultation-privee', $scene, $scene->title, causer: auth()->user());
        }
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    protected function getHeaderActions(): array
    {
        return SceneResource::headerActions();
    }
}
