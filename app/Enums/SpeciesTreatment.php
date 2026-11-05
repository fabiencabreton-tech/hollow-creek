<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Traitement visuel des portraits et des fiches de bestiaire.
 */
enum SpeciesTreatment: string implements HasLabel
{
    case Humain = 'humain';
    case Yurei = 'yurei';
    case Onryo = 'onryo';
    case Revenu = 'revenu';
    case Rappele = 'rappele';
    case Medium = 'medium';
    case Occulte = 'occulte';
    case Maudit = 'maudit';

    public function label(): string
    {
        return match ($this) {
            self::Humain => 'Cadre crème simple',
            self::Yurei => 'Portrait désaturé et translucide',
            self::Onryo => 'Translucide, liseré rouge sombre',
            self::Revenu => 'Cadre de brume daté « 1996 »',
            self::Rappele => 'Teinte froide, liseré bleu-gris',
            self::Medium => 'Halo de lanterne',
            self::Occulte => 'Liseré bordeaux',
            self::Maudit => 'Liseré d’encre',
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
