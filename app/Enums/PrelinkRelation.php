<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PrelinkRelation: string implements HasLabel
{
    case Sibling = 'fratrie';
    case Parent = 'parent';
    case Child = 'enfant';
    case Grandparent = 'grand-parent';
    case Grandchild = 'petit-enfant';
    case Cousin = 'cousin';
    case AuntUncle = 'oncle-tante';
    case NieceNephew = 'neveu-niece';
    case Spouse = 'conjoint';
    case InLaw = 'belle-famille';
    case Other = 'autre';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sibling => 'Frère ou sœur',
            self::Parent => 'Parent',
            self::Child => 'Enfant',
            self::Grandparent => 'Grand-parent',
            self::Grandchild => 'Petit-enfant',
            self::Cousin => 'Cousin ou cousine',
            self::AuntUncle => 'Oncle ou tante',
            self::NieceNephew => 'Neveu ou nièce',
            self::Spouse => 'Conjoint ou conjointe',
            self::InLaw => 'Belle-famille',
            self::Other => 'Autre lien familial',
        };
    }
}
