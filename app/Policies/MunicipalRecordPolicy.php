<?php

namespace App\Policies;

class MunicipalRecordPolicy extends UniverseContentPolicy
{
    protected string $permission = 'univers.gerer';
}
