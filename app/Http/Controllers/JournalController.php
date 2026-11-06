<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Models\Character;
use App\Models\JournalEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Journal personnel d'un personnage. Les entrées privées ne quittent jamais le
 * serveur pour quelqu'un d'autre que leur auteur (et la modération des scènes).
 */
class JournalController extends Controller
{
    public function index(Request $request, Character $character): View
    {
        Gate::authorize('view', $character);

        return view('journal.index', [
            'character' => $character,
            'entries' => $character->journalEntries()
                ->visibleTo($request->user())
                ->latest('id')
                ->paginate(10),
            'isOwner' => $this->isOwner($request, $character),
        ]);
    }

    public function show(Request $request, Character $character, JournalEntry $entry): View
    {
        Gate::authorize('view', $character);
        abort_unless($entry->character_id === $character->getKey() && $entry->isVisibleTo($request->user()), 404);

        return view('journal.show', [
            'character' => $character,
            'entry' => $entry,
            'isOwner' => $this->isOwner($request, $character) && $entry->user_id === $request->user()->getKey(),
        ]);
    }

    public function create(Request $request, Character $character): View
    {
        $this->ensureOwner($request, $character);

        return view('journal.form', ['character' => $character, 'entry' => new JournalEntry]);
    }

    public function store(Request $request, Character $character): RedirectResponse
    {
        $this->ensureOwner($request, $character);
        $data = $this->validated($request);

        $entry = new JournalEntry;
        $entry->forceFill($data + [
            'character_id' => $character->getKey(),
            'user_id' => $request->user()->getKey(),
        ])->save();

        return redirect()->route('journal.show', [$character, $entry])->with('status', $entry->is_private ? 'Entrée privée enregistrée.' : 'Entrée publiée.');
    }

    public function edit(Request $request, Character $character, JournalEntry $entry): View
    {
        $this->ensureOwner($request, $character);
        abort_unless($entry->character_id === $character->getKey(), 404);
        Gate::authorize('update', $entry);

        return view('journal.form', ['character' => $character, 'entry' => $entry]);
    }

    public function update(Request $request, Character $character, JournalEntry $entry): RedirectResponse
    {
        $this->ensureOwner($request, $character);
        abort_unless($entry->character_id === $character->getKey(), 404);
        Gate::authorize('update', $entry);

        $entry->forceFill($this->validated($request))->save();

        return redirect()->route('journal.show', [$character, $entry])->with('status', 'Entrée enregistrée.');
    }

    public function destroy(Request $request, Character $character, JournalEntry $entry): RedirectResponse
    {
        abort_unless($entry->character_id === $character->getKey(), 404);
        Gate::authorize('delete', $entry);

        $entry->delete();

        return redirect()->route('journal.index', $character)->with('status', 'Entrée supprimée.');
    }

    /**
     * @return array{title: ?string, rp_date: ?string, body: string, is_private: bool}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'rp_date' => ['nullable', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:'.JournalEntry::MAX_LENGTH],
            'visibility' => ['required', 'in:public,prive'],
        ], [], [
            'title' => 'titre',
            'rp_date' => 'date dans le jeu',
            'body' => 'texte',
            'visibility' => 'visibilité',
        ]);

        return [
            'title' => filled($data['title'] ?? null) ? trim($data['title']) : null,
            'rp_date' => filled($data['rp_date'] ?? null) ? trim($data['rp_date']) : null,
            'body' => trim($data['body']),
            'is_private' => $data['visibility'] === 'prive',
        ];
    }

    private function isOwner(Request $request, Character $character): bool
    {
        $user = $request->user();

        return $user !== null
            && $character->user_id === $user->getKey()
            && $character->status === CharacterStatus::Accepted
            && $user->hasVerifiedEmail()
            && ! $user->isSuspended();
    }

    private function ensureOwner(Request $request, Character $character): void
    {
        abort_unless($this->isOwner($request, $character), 403);
    }
}
