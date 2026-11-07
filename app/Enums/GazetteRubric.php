<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GazetteRubric: string implements HasLabel
{
    case Une = 'a-la-une';
    case Actualites = 'actualites-locales';
    case FaitsDivers = 'faits-divers';
    case Rumeurs = 'rumeurs-et-temoignages';
    case VieLocale = 'vie-locale';
    case Annonces = 'annonces';
    case AvisDeces = 'avis-de-deces';

    public function label(): string
    {
        return match ($this) {
            self::Une => 'À la une',
            self::Actualites => 'Actualités locales',
            self::FaitsDivers => 'Faits divers',
            self::Rumeurs => 'Rumeurs et témoignages',
            self::VieLocale => 'Vie locale',
            self::Annonces => 'Annonces',
            self::AvisDeces => 'Avis de décès',
        };
    }

    /**
     * Pictogramme de l'onglet (voir partials/icons).
     */
    public function icon(): string
    {
        return match ($this) {
            self::Une => 'lune',
            self::Actualites => 'torii',
            self::FaitsDivers => 'journal',
            self::Rumeurs => 'murmure',
            self::VieLocale => 'maison',
            self::Annonces => 'parchemin',
            self::AvisDeces => 'lanterne',
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
