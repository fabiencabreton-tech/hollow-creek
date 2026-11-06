<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PrelinkStatus: string implements HasColor, HasLabel
{
    case Open = 'ouvert';
    case Taken = 'pourvu';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Recherché',
            self::Taken => 'Pourvu',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Taken => 'success',
        };
    }
}
