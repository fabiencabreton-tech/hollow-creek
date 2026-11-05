<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GazetteRubric: string implements HasLabel
{
    case Une = 'a-la-une';
    case FaitsDivers = 'faits-divers';
    case VieLocale = 'vie-locale';
    case Annonces = 'annonces';
    case AvisDeces = 'avis-de-deces';

    public function label(): string
    {
        return match ($this) {
            self::Une => 'À la une',
            self::FaitsDivers => 'Faits divers',
            self::VieLocale => 'Vie locale',
            self::Annonces => 'Annonces',
            self::AvisDeces => 'Avis de décès',
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
