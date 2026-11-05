<?php

namespace App\Policies;

use App\Enums\CharacterStatus;
use App\Models\Character;
use App\Models\User;

/**
 * Une fiche n'est publique qu'une fois acceptée. Avant, seuls son auteur et
 * l'équipe chargée des fiches peuvent la lire.
 */
class CharacterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('personnages.valider');
    }

    public function view(?User $user, Character $character): bool
    {
        if ($character->status === CharacterStatus::Accepted) {
            return true;
        }

        return $user !== null && ($user->is($character->user) || $user->can('personnages.valider'));
    }

    public function update(User $user, Character $character): bool
    {
        return $user->is($character->user) && $character->isEditableByOwner() && ! $user->isSuspended();
    }

    public function delete(User $user, Character $character): bool
    {
        return $this->update($user, $character);
    }

    public function archive(User $user, Character $character): bool
    {
        return $character->status === CharacterStatus::Accepted
            && ($user->is($character->user) || $user->can('personnages.valider'));
    }

    public function review(User $user, Character $character): bool
    {
        return $user->can('personnages.valider') && ! $user->is($character->user);
    }
}
