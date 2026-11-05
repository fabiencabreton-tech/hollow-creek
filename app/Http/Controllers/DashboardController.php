<?php

namespace App\Http\Controllers;

use App\Enums\RelationshipStatus;
use App\Models\ContactTicket;
use App\Models\Conversation;
use App\Models\Relationship;
use App\Services\CharacterWorkflow;
use App\Support\SceneInbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'isStaff' => $user->isStaff(),
            'hasTwoFactor' => $user->hasConfirmedTwoFactor(),
            'greeting' => $this->greeting(),
            'characters' => $user->characters()->with('species')->whereNot('status', \App\Enums\CharacterStatus::Archived->value)->orderBy('first_name')->get(),
            'remaining' => app(CharacterWorkflow::class)->remainingSlots($user),
            'invitations' => SceneInbox::invitations($user),
            'activeScenes' => SceneInbox::active($user),
            'drafts' => SceneInbox::drafts($user),
            'unreadMessages' => Conversation::unreadCountFor($user),
            'pendingRelations' => Relationship::query()
                ->where('status', RelationshipStatus::Proposed->value)
                ->whereIn('target_character_id', $user->characters()->select('id'))
                ->count(),
            'openTickets' => ContactTicket::query()->where('user_id', $user->getKey())->where('status', ContactTicket::STATUS_ANSWERED)->count(),
        ]);
    }

    protected function greeting(): string
    {
        $hour = (int) now()->format('G');

        return ($hour >= 18 || $hour < 5) ? 'Bonsoir' : 'Bonjour';
    }
}
