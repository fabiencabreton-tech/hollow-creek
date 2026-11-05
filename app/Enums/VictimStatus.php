<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VictimStatus: string implements HasColor, HasLabel
{
    case Administrative = 'administratif';
    case Open = 'ouvert';
    case Reserved = 'reserve';
    case Played = 'incarne';

    public function getLabel(): string
    {
        return match ($this) {
            self::Administrative => 'Personnage de l’équipe',
            self::Open => 'Peut être incarné',
            self::Reserved => 'Fiche en examen',
            self::Played => 'Incarné',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Administrative => 'gray',
            self::Open => 'success',
            self::Reserved => 'warning',
            self::Played => 'info',
        };
    }
}
