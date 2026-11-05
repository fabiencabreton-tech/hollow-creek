<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Règles communes aux pseudos des joueurs.
 */
class Pseudo
{
    /**
     * Lettres (accents compris), chiffres, espaces, tirets, apostrophes, points et tirets bas.
     */
    public const PATTERN = "/^[\\pL\\pN][\\pL\\pN \\-'’._]*$/u";

    /**
     * Forme normalisée utilisée pour l'unicité : « Élise-Rose » devient « eliserose ».
     */
    public static function key(string $pseudo): string
    {
        $ascii = Str::lower(Str::ascii(trim($pseudo)));

        return (string) preg_replace('/[^a-z0-9]+/', '', $ascii);
    }

    /**
     * Nettoie la saisie : espaces superflus retirés.
     */
    public static function clean(string $pseudo): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $pseudo));
    }
}
