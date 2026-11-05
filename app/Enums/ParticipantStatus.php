<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ParticipantStatus: string implements HasColor, HasLabel
{
    case Invited = 'invite';
    case Accepted = 'accepte';
    case Declined = 'refuse';
    case Left = 'parti';

    public function getLabel(): string
    {
        return match ($this) {
            self::Invited => 'Invitation en attente',
            self::Accepted => 'Participe',
            self::Declined => 'Invitation déclinée',
            self::Left => 'A quitté la scène',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Invited => 'warning',
            self::Accepted => 'success',
            default => 'gray',
        };
    }
}
