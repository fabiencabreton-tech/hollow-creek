<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FamilyRequestStatus: string implements HasColor, HasLabel
{
    case Pending = 'en-attente';
    case Accepted = 'acceptee';
    case Refused = 'refusee';
    case Cancelled = 'annulee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Accepted => 'Acceptée',
            self::Refused => 'Refusée',
            self::Cancelled => 'Annulée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Accepted => 'success',
            self::Refused => 'danger',
            self::Cancelled => 'gray',
        };
    }
}
