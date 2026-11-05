<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('scenes.moderer');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->can('scenes.moderer');
    }

    /**
     * Seul l'auteur corrige sa réponse, tant que la scène n'est ni terminée ni archivée.
     */
    public function update(User $user, Post $post): bool
    {
        $scene = $post->scene;

        return ! $user->isSuspended()
            && $post->user_id === $user->getKey()
            && ! $post->trashed()
            && $scene !== null
            && ! $scene->trashed()
            && $scene->status->allowsEditing();
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->can('scenes.moderer');
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->can('scenes.moderer');
    }
}
