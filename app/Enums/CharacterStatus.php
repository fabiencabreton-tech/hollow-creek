<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CharacterStatus: string implements HasColor, HasLabel
{
    case Draft = 'brouillon';
    case Pending = 'en_attente';
    case ChangesRequested = 'modifications_demandees';
    case Accepted = 'acceptee';
    case Refused = 'refusee';
    case Archived = 'archivee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Pending => 'En attente',
            self::ChangesRequested => 'Modifications demandées',
            self::Accepted => 'Acceptée',
            self::Refused => 'Refusée',
            self::Archived => 'Archivée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::ChangesRequested => 'info',
            self::Accepted => 'success',
            self::Refused, self::Archived => 'danger',
        };
    }

    /**
     * Le joueur peut encore modifier sa fiche.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::ChangesRequested], true);
    }
}
