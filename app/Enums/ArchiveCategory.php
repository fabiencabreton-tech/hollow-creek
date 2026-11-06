<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ArchiveCategory: string implements HasLabel
{
    case Council = 'conseil';
    case CivilRegistry = 'etat-civil';
    case Planning = 'urbanisme';
    case Heritage = 'patrimoine';
    case Press = 'presse';
    case Health = 'sante';
    case Other = 'divers';

    public function getLabel(): string
    {
        return match ($this) {
            self::Council => 'Conseil municipal',
            self::CivilRegistry => 'État civil',
            self::Planning => 'Urbanisme et cadastre',
            self::Heritage => 'Histoire et patrimoine',
            self::Press => 'Coupures de presse',
            self::Health => 'Santé et hôpital',
            self::Other => 'Divers',
        };
    }
}
