<?php

namespace App\Policies;

use App\Models\FamilyRequest;
use App\Models\User;

/**
 * Demandes d'intégration aux familles : examinées par l'équipe qui valide les fiches.
 */
class FamilyRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('personnages.valider');
    }

    public function view(User $user, FamilyRequest $request): bool
    {
        return $user->can('personnages.valider');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, FamilyRequest $request): bool
    {
        return $user->can('personnages.valider');
    }

    public function delete(User $user, FamilyRequest $request): bool
    {
        return false;
    }
}
