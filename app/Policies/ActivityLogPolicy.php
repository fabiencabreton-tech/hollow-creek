<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

/**
 * Le journal se consulte, il ne se modifie pas.
 */
class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('journal.voir');
    }

    public function view(User $user, ActivityLog $entry): bool
    {
        return $user->can('journal.voir');
    }
}
