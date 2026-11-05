<?php

namespace App\Policies;

use App\Models\ContactTicket;
use App\Models\User;

class ContactTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contact.repondre');
    }

    public function view(User $user, ContactTicket $ticket): bool
    {
        return $user->can('contact.repondre');
    }

    public function update(User $user, ContactTicket $ticket): bool
    {
        return $user->can('contact.repondre');
    }
}
