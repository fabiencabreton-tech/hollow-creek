<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RelationshipKind: string implements HasLabel
{
    case Friendship = 'amitie';
    case Romance = 'romance';
    case Family = 'famille';
    case Rivalry = 'rivalite';
    case Other = 'autre';

    public function getLabel(): string
    {
        return match ($this) {
            self::Friendship => 'Amitié',
            self::Romance => 'Romance',
            self::Family => 'Famille',
            self::Rivalry => 'Rivalité',
            self::Other => 'Autre lien',
        };
    }
}
