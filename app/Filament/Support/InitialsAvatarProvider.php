<?php

namespace App\Filament\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Avatar de l'administration : les initiales du pseudo, dessinées sur place.
 * Remplace le service externe par défaut de Filament : aucun pseudo ne quitte le site.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $name = (string) ($record->getAttribute('pseudo') ?? '?');
        $initials = Str::upper(Str::substr(Str::ascii($name), 0, 2)) ?: '?';
        $initials = htmlspecialchars($initials, ENT_QUOTES | ENT_XML1);

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64"><rect width="64" height="64" fill="#5e1a24"/><text x="32" y="41" font-family="Georgia, serif" font-size="26" fill="#efe6d2" text-anchor="middle">{$initials}</text></svg>
        SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
