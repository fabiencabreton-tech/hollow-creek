<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EventStatus: string implements HasColor, HasLabel
{
    case Announced = 'annonce';
    case Ongoing = 'en_cours';
    case Finished = 'termine';

    public function getLabel(): string
    {
        return match ($this) {
            self::Announced => 'Annoncé',
            self::Ongoing => 'En cours',
            self::Finished => 'Terminé',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Announced => 'info',
            self::Ongoing => 'success',
            self::Finished => 'gray',
        };
    }
}
