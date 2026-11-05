<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SceneVisibility: string implements HasLabel
{
    case Public = 'publique';
    case Private = 'privee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Public => 'Publique',
            self::Private => 'Privée',
        };
    }
}
