<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestigationStatus: string implements HasColor, HasLabel
{
    case Open = 'ouverte';
    case Closed = 'close';
    case Cold = 'suspendue';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'En cours',
            self::Closed => 'Résolue',
            self::Cold => 'Au point mort',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Closed => 'success',
            self::Cold => 'gray',
        };
    }
}
