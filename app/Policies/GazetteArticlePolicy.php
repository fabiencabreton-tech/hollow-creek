<?php

namespace App\Policies;

/**
 * Articles de la Gazette : permission « gazette.gerer » (par exemple pour un rôle
 * « rédaction de la Gazette » créé dans l'administration).
 */
class GazetteArticlePolicy extends UniverseContentPolicy
{
    protected string $permission = 'gazette.gerer';
}
