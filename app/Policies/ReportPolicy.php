<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('signalements.traiter');
    }

    public function view(User $user, Report $report): bool
    {
        return $user->can('signalements.traiter');
    }

    public function update(User $user, Report $report): bool
    {
        return $user->can('signalements.traiter');
    }
}
