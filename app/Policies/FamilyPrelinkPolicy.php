<?php

namespace App\Policies;

use App\Models\FamilyPrelink;
use App\Models\User;

/**
 * Préliens : gérés par leur auteur sur le site ; la modération peut les retirer.
 */
class FamilyPrelinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('signalements.traiter');
    }

    public function view(User $user, FamilyPrelink $prelink): bool
    {
        return $user->can('signalements.traiter');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, FamilyPrelink $prelink): bool
    {
        return false;
    }

    public function delete(User $user, FamilyPrelink $prelink): bool
    {
        return $user->can('signalements.traiter');
    }

    public function restore(User $user, FamilyPrelink $prelink): bool
    {
        return $user->can('signalements.traiter');
    }
}
