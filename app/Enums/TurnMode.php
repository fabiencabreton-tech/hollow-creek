<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TurnMode: string implements HasLabel
{
    case Free = 'libre';
    case Rounds = 'tour';

    public function getLabel(): string
    {
        return match ($this) {
            self::Free => 'Libre',
            self::Rounds => 'Tour par tour',
        };
    }
}
