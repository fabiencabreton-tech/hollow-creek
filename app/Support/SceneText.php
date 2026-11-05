<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Mise en forme des réponses : Markdown limité (gras, italique, paragraphes, citation,
 * séparateur), HTML neutralisé, et dialogues « entre guillemets » repérés pour
 * recevoir la couleur de dialogue du personnage.
 */
class SceneText
{
    public static function render(?string $text): HtmlString
    {
        $html = Markdown::render($text)->toHtml();

        if ($html === '') {
            return new HtmlString('');
        }

        // Seulement dans le texte, jamais à l'intérieur d'une balise.
        $html = (string) preg_replace_callback(
            '/(«[^«»<>]{1,2000}»|“[^“”<>]{1,2000}”)/u',
            fn (array $match): string => '<span class="dialogue">'.$match[1].'</span>',
            $html,
        );

        return new HtmlString($html);
    }

    public static function wordCount(?string $text): int
    {
        $plain = trim((string) preg_replace('/[*_>#`\-]+/u', ' ', (string) $text));

        if ($plain === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $plain) ?: []);
    }
}
