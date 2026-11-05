<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Distingue les véritables natures surnaturelles des spécialisations et des conditions.
 */
enum SpeciesKind: string implements HasLabel
{
    case Nature = 'nature';
    case Specialisation = 'specialisation';
    case Condition = 'condition';

    public function getLabel(): string
    {
        return match ($this) {
            self::Nature => 'Nature',
            self::Specialisation => 'Spécialisation',
            self::Condition => 'Condition',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Nature => 'Natures',
            self::Specialisation => 'Spécialisations',
            self::Condition => 'Conditions',
        };
    }

    public function intro(): string
    {
        return match ($this) {
            self::Nature => 'Chaque personnage a une seule nature.',
            self::Specialisation => 'Des dons ou des savoirs qui s’ajoutent à une nature, sans en changer.',
            self::Condition => 'Un état qui peut toucher un personnage, quelle que soit sa nature.',
        };
    }
}
