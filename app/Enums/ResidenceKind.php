<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ResidenceKind: string implements HasLabel
{
    case House = 'maison';
    case Apartment = 'appartement';
    case Room = 'chambre';
    case Shop = 'commerce';
    case Other = 'autre';

    public function getLabel(): string
    {
        return match ($this) {
            self::House => 'Maison',
            self::Apartment => 'Appartement',
            self::Room => 'Chambre',
            self::Shop => 'Logement au-dessus d’un commerce',
            self::Other => 'Autre',
        };
    }
}
