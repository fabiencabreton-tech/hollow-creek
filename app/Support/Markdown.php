<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Affichage des textes rédigés en Markdown dans l'administration.
 * Le HTML saisi est échappé et les liens dangereux (javascript:, data:) neutralisés :
 * aucun texte ne peut exécuter de script sur le site.
 */
class Markdown
{
    public static function render(?string $text): HtmlString
    {
        if (blank($text)) {
            return new HtmlString('');
        }

        return new HtmlString(Str::markdown((string) $text, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]));
    }
}
