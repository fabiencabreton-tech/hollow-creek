<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use App\Models\UserBlock;

/**
 * Une conversation n'est lisible que par ses participants encore présents.
 * L'équipe n'y a pas accès : elle ne voit que les messages signalés.
 */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        $participant = $conversation->participantFor($user);

        return $participant !== null && $participant->left_at === null;
    }

    public function send(User $user, Conversation $conversation): bool
    {
        if ($user->isSuspended() || ! $this->view($user, $conversation)) {
            return false;
        }

        $others = $conversation->others($user);

        if ($others->isEmpty()) {
            return false;
        }

        // À deux, un blocage dans un sens ou dans l'autre ferme la conversation.
        if ($others->count() === 1) {
            return ! UserBlock::between($user, $others->first());
        }

        return true;
    }
}
