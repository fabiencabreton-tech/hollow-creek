<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SceneStatus: string implements HasColor, HasLabel
{
    case Ongoing = 'en_cours';
    case Paused = 'en_pause';
    case Finished = 'terminee';
    case Archived = 'archivee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ongoing => 'En cours',
            self::Paused => 'En pause',
            self::Finished => 'Terminée',
            self::Archived => 'Archivée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ongoing => 'success',
            self::Paused => 'warning',
            self::Finished => 'info',
            self::Archived => 'gray',
        };
    }

    /**
     * Une réponse publiée peut encore être corrigée.
     */
    public function allowsEditing(): bool
    {
        return $this === self::Ongoing || $this === self::Paused;
    }
}
