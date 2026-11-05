<?php

namespace App\Policies;

/**
 * Archives secrètes : réservées aux administrateurs (permission « archives.lire »,
 * jamais donnée aux modérateurs).
 */
class LoreEntryPolicy extends UniverseContentPolicy
{
    protected string $permission = 'archives.lire';
}
