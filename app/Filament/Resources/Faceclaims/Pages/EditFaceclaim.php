<?php

namespace App\Filament\Resources\Faceclaims\Pages;

use App\Filament\Resources\Faceclaims\FaceclaimResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Pas de suppression : l'historique des demandes reste rattaché au nom.
 */
class EditFaceclaim extends EditRecord
{
    protected static string $resource = FaceclaimResource::class;
}
