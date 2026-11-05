<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FeedKind: string implements HasLabel
{
    case Thought = 'pensee';
    case Text = 'texte';
    case Image = 'image';
    case Announcement = 'annonce';

    public function getLabel(): string
    {
        return match ($this) {
            self::Thought => 'Pensée',
            self::Text => 'Court texte',
            self::Image => 'Image',
            self::Announcement => 'Annonce',
        };
    }
}
