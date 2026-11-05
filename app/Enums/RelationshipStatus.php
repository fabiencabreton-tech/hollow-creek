<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RelationshipStatus: string implements HasLabel
{
    case Proposed = 'proposee';
    case Accepted = 'acceptee';
    case Declined = 'refusee';
    case Ended = 'terminee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Proposed => 'Proposé',
            self::Accepted => 'Accepté',
            self::Declined => 'Décliné',
            self::Ended => 'Terminé',
        };
    }
}
