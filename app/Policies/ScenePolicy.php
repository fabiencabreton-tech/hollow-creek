<?php

namespace App\Policies;

use App\Enums\ParticipantStatus;
use App\Enums\SceneStatus;
use App\Enums\TurnMode;
use App\Models\Character;
use App\Models\Scene;
use App\Models\User;

/**
 * Accès aux scènes. Une scène privée n'existe que pour ses participants
 * (invités, actifs ou partis) et pour l'équipe chargée de la modération.
 */
class ScenePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('scenes.moderer');
    }

    public function view(?User $user, Scene $scene): bool
    {
        if ($user?->can('scenes.moderer')) {
            return true;
        }

        if ($scene->trashed()) {
            return false;
        }

        if (! $scene->isPrivate()) {
            return true;
        }

        $participant = $scene->participantFor($user);

        return $participant !== null && $participant->status !== ParticipantStatus::Declined;
    }

    public function create(User $user): bool
    {
        return ! $user->isSuspended()
            && Character::query()->where('user_id', $user->getKey())->accepted()->exists();
    }

    /**
     * Écrire une réponse : participant actif, scène en cours et, en tour par tour, c'est son tour.
     */
    public function reply(User $user, Scene $scene): bool
    {
        if ($user->isSuspended() || $scene->trashed() || $scene->status !== SceneStatus::Ongoing) {
            return false;
        }

        if (! $scene->isActiveParticipant($user)) {
            return false;
        }

        return $scene->turn_mode === TurnMode::Free
            || $scene->next_user_id === null
            || $scene->next_user_id === $user->getKey();
    }

    /**
     * Préparer un brouillon : participant actif d'une scène qui n'est ni terminée ni archivée.
     */
    public function draft(User $user, Scene $scene): bool
    {
        return ! $user->isSuspended() && ! $scene->trashed() && $scene->status->allowsEditing() && $scene->isActiveParticipant($user);
    }

    /**
     * Statut, invitations : la personne qui a ouvert la scène, ou l'équipe.
     */
    public function manage(User $user, Scene $scene): bool
    {
        if ($user->can('scenes.moderer')) {
            return true;
        }

        return ! $user->isSuspended() && ! $scene->trashed() && $scene->user_id === $user->getKey();
    }

    public function invite(User $user, Scene $scene): bool
    {
        return $scene->status->allowsEditing() && $this->manage($user, $scene);
    }

    public function join(User $user, Scene $scene): bool
    {
        if ($user->isSuspended() || $scene->trashed() || $scene->isPrivate() || ! $scene->is_open || $scene->status !== SceneStatus::Ongoing) {
            return false;
        }

        $participant = $scene->participantFor($user);

        return $participant === null || in_array($participant->status, [ParticipantStatus::Declined, ParticipantStatus::Left], true);
    }

    public function leave(User $user, Scene $scene): bool
    {
        return $scene->user_id !== $user->getKey() && $scene->isActiveParticipant($user);
    }

    public function export(?User $user, Scene $scene): bool
    {
        return in_array($scene->status, [SceneStatus::Finished, SceneStatus::Archived], true) && $this->view($user, $scene);
    }

    public function update(User $user, Scene $scene): bool
    {
        return $user->can('scenes.moderer');
    }

    public function delete(User $user, Scene $scene): bool
    {
        return $user->can('scenes.moderer');
    }

    public function restore(User $user, Scene $scene): bool
    {
        return $user->can('scenes.moderer');
    }
}
