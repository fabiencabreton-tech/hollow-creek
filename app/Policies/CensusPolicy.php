<?php

namespace App\Policies;

class CensusPolicy extends UniverseContentPolicy
{
    protected string $permission = 'recensements.gerer';
}
