<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Enums\PrelinkStatus;
use App\Enums\RelationshipStatus;
use App\Enums\SceneStatus;
use App\Models\Census;
use App\Models\Character;
use App\Models\ContactTicket;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\FamilyPrelink;
use App\Models\GazetteArticle;
use App\Models\Relationship;
use App\Models\Scene;
use App\Models\UserNotification;
use App\Services\CharacterWorkflow;
use App\Support\SceneInbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Tableau de bord. Un joueur avec un personnage accepté y trouve son jeu du jour ;
 * un compte encore sans personnage accepté y suit l'avancement de sa fiche.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $characters = $user->characters()
            ->with(['species', 'family', 'residence', 'reviews'])
            ->whereNot('status', CharacterStatus::Archived->value)
            ->orderBy('first_name')
            ->get();
        $accepted = $characters->where('status', CharacterStatus::Accepted)->values();

        $common = [
            'user' => $user,
            'isStaff' => $user->isStaff(),
            'hasTwoFactor' => $user->hasConfirmedTwoFactor(),
            'greeting' => $this->greeting(),
            'characters' => $characters,
            'remaining' => app(CharacterWorkflow::class)->remainingSlots($user),
            'unreadMessages' => Conversation::unreadCountFor($user),
            'openTickets' => ContactTicket::query()->where('user_id', $user->getKey())->where('status', ContactTicket::STATUS_ANSWERED)->count(),
        ];

        if ($accepted->isEmpty()) {
            return view('dashboard-pending', $common + [
                'progress' => $characters->map(fn (Character $character): array => [
                    'character' => $character,
                    'missing' => in_array($character->status, [CharacterStatus::Draft, CharacterStatus::ChangesRequested], true)
                        ? $character->missingForSubmission()
                        : [],
                    'lastReview' => $character->reviews->first(),
                ]),
            ]);
        }

        $active = $accepted->firstWhere('id', $user->active_character_id) ?? $accepted->first();
        $scenes = SceneInbox::active($user);
        $myCharacterIds = $user->characters()->pluck('id');

        return view('dashboard', $common + [
            'accepted' => $accepted,
            'active' => $active,
            'invitations' => SceneInbox::invitations($user),
            'yourTurn' => $scenes->where('yourTurn', true)->values(),
            'waiting' => $scenes->where('yourTurn', false)->values(),
            'drafts' => SceneInbox::drafts($user),
            'census' => Census::query()->open()->latest('id')->first(),
            'notifications' => UserNotification::query()->where('user_id', $user->getKey())->latest('created_at')->limit(5)->get(),
            'unreadNotifications' => UserNotification::query()->where('user_id', $user->getKey())->whereNull('read_at')->count(),
            'pendingRelations' => Relationship::query()
                ->where('status', RelationshipStatus::Proposed->value)
                ->whereIn('target_character_id', $myCharacterIds)
                ->count(),
            'prelinks' => FamilyPrelink::query()
                ->where('status', PrelinkStatus::Open->value)
                ->whereNotIn('character_id', $myCharacterIds)
                ->with(['family', 'character'])
                ->latest('id')
                ->limit(4)
                ->get(),
            'openScenes' => Scene::query()
                ->listed()
                ->where('is_open', true)
                ->where('status', SceneStatus::Ongoing->value)
                ->whereDoesntHave('participants', fn ($query) => $query->where('user_id', $user->getKey()))
                ->with('location')
                ->latest('last_post_at')
                ->limit(4)
                ->get(),
            'news' => GazetteArticle::query()->published()->latest('published_at')->limit(3)->get(),
            'events' => Event::query()->active()->latest('published_at')->limit(3)->get(),
        ]);
    }

    protected function greeting(): string
    {
        $hour = (int) now()->format('G');

        return ($hour >= 18 || $hour < 5) ? 'Bonsoir' : 'Bonjour';
    }
}
