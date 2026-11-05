<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Demande portant sur une célébrité du bottin.
 * Les trois premiers statuts sont « actifs » : une seule demande active par célébrité.
 */
enum ClaimStatus: string implements HasColor, HasLabel
{
    case Unconfirmed = 'a_confirmer';
    case Pending = 'en_attente';
    case Reserved = 'reservee';
    case Taken = 'prise';
    case Refused = 'refusee';
    case Expired = 'expiree';
    case Released = 'liberee';

    public const ACTIVE = ['en_attente', 'reservee', 'prise'];

    public function getLabel(): string
    {
        return match ($this) {
            self::Unconfirmed => 'Adresse à confirmer',
            self::Pending => 'Demande en cours',
            self::Reserved => 'Réservé',
            self::Taken => 'Pris',
            self::Refused => 'Refusée',
            self::Expired => 'Expirée',
            self::Released => 'Libérée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Reserved => 'info',
            self::Taken => 'success',
            default => 'gray',
        };
    }

    public function isActive(): bool
    {
        return in_array($this->value, self::ACTIVE, true);
    }
}
