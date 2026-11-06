<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AdCategory: string implements HasLabel
{
    case Jobs = 'emploi';
    case Sales = 'vente';
    case Housing = 'logement';
    case Services = 'service';
    case Wanted = 'recherche';
    case Personal = 'message';
    case Lost = 'perdu';

    public function getLabel(): string
    {
        return match ($this) {
            self::Jobs => 'Emplois',
            self::Sales => 'Ventes et objets',
            self::Housing => 'Logement',
            self::Services => 'Services',
            self::Wanted => 'Recherches',
            self::Personal => 'Messages personnels',
            self::Lost => 'Objets perdus et trouvés',
        };
    }
}
