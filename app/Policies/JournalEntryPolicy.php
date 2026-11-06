<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    public function view(?User $user, JournalEntry $entry): bool
    {
        return $entry->isVisibleTo($user);
    }

    public function update(User $user, JournalEntry $entry): bool
    {
        return $user->getKey() === $entry->user_id && ! $user->isSuspended();
    }

    public function delete(User $user, JournalEntry $entry): bool
    {
        return $user->getKey() === $entry->user_id;
    }
}
