<?php

namespace App\Policies;

class EventPolicy extends UniverseContentPolicy
{
    protected string $permission = 'evenements.gerer';
}
