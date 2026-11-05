<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Traitement visuel discret des portraits selon l'espèce (direction artistique).
 */
enum SpeciesTreatment: string implements HasLabel
{
    case Humain = 'humain';
    case Revenant = 'revenant';
    case Fantome = 'fantome';
    case Medium = 'medium';
    case Sorciere = 'sorciere';
    case Chasseur = 'chasseur';

    public function label(): string
    {
        return match ($this) {
            self::Humain => 'Cadre crème simple',
            self::Revenant => 'Cadre de brume daté « 1996 »',
            self::Fantome => 'Portrait désaturé et translucide',
            self::Medium => 'Halo de lanterne',
            self::Sorciere => 'Liseré bordeaux',
            self::Chasseur => 'Liseré vert sapin',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
