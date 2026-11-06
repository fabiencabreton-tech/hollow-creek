<?php

namespace App\Policies;

class InvestigationPolicy extends UniverseContentPolicy
{
    protected string $permission = 'enquetes.gerer';
}
