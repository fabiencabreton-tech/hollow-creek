<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PredefinedStatus: string implements HasColor, HasLabel
{
    case Open = 'ouvert';
    case UnderReview = 'en_examen';
    case Taken = 'pris';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Recherché',
            self::UnderReview => 'Fiche en examen',
            self::Taken => 'Pris',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'success',
            self::UnderReview => 'warning',
            self::Taken => 'gray',
        };
    }
}
