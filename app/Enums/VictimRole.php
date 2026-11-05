<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VictimRole: string implements HasLabel
{
    case Patient = 'patient';
    case Staff = 'personnel';
    case Other = 'autre';

    public function getLabel(): string
    {
        return match ($this) {
            self::Patient => 'Patient',
            self::Staff => 'Membre du personnel',
            self::Other => 'Autre',
        };
    }
}
