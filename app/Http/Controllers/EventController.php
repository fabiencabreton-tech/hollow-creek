<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Enums\EventStatus;
use App\Models\Character;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\GazetteArticle;
use App\Models\Scene;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Événements de l'équipe : calendrier, inscriptions, scènes et articles rattachés.
 */
class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::query()->published()->with('location')->orderBy('sort_order')->latest('published_at')->get();

        return view('events.index', [
            'current' => $events->where('status', '!=', EventStatus::Finished),
            'past' => $events->where('status', EventStatus::Finished),
        ]);
    }

    public function show(Request $request, Event $event): View
    {
        $isPreview = ! $event->is_published;
        abort_if($isPreview && ! $request->user()?->can('evenements.gerer'), 404);

        $event->load(['location', 'participants.character']);
        $user = $request->user();

        return view('events.show', [
            'event' => $event,
            'isPreview' => $isPreview,
            'scenes' => Scene::query()->listed()->where('event_id', $event->getKey())->with('location')->latest('last_post_at')->get(),
            'articles' => GazetteArticle::query()->published()->where('event_id', $event->getKey())->latest('published_at')->get(),
            'myCharacters' => $user
                ? Character::query()->where('user_id', $user->getKey())->accepted()
                    ->whereNotIn('id', $event->participants->pluck('character_id'))->orderBy('first_name')->get()
                : collect(),
            'myRegistrations' => $user ? $event->participants->where('user_id', $user->getKey()) : collect(),
        ]);
    }

    public function register(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->is_published, 404);

        if (! $event->acceptsRegistrations()) {
            throw ValidationException::withMessages(['character_id' => 'Les inscriptions à cet événement sont closes.']);
        }

        $data = $request->validate(['character_id' => ['required', 'integer']], [], ['character_id' => 'personnage']);
        $character = Character::query()->find($data['character_id']);

        if ($character === null || $character->user_id !== $request->user()->getKey() || $character->status !== CharacterStatus::Accepted) {
            throw ValidationException::withMessages(['character_id' => 'Choisissez l’un de vos personnages acceptés.']);
        }

        EventParticipant::query()->firstOrCreate(
            ['event_id' => $event->getKey(), 'character_id' => $character->getKey()],
            ['user_id' => $request->user()->getKey(), 'created_at' => now()],
        );

        return redirect()->route('events.show', $event)->with('status', $character->fullName().' participe à l’événement.');
    }

    public function unregister(Request $request, Event $event, EventParticipant $participant): RedirectResponse
    {
        abort_unless($participant->event_id === $event->getKey() && $participant->user_id === $request->user()->getKey(), 404);

        $participant->delete();

        return redirect()->route('events.show', $event)->with('status', 'Inscription retirée.');
    }
}
