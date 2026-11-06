<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Http\Controllers\Concerns\ChecksPreview;
use App\Models\Character;
use App\Models\Clue;
use App\Models\Investigation;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\ClueService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Enquêtes : les mystères ouverts par l'équipe. Un indice non public n'est envoyé
 * qu'aux joueurs dont un personnage le détient ; le contrôle se fait ici, côté serveur.
 */
class InvestigationController extends Controller
{
    use ChecksPreview;

    public function index(Request $request): View
    {
        $investigations = Investigation::query()
            ->published()
            ->withCount(['clues as public_clues_count' => fn ($query) => $query->where('is_public', true)])
            ->latest('updated_at')
            ->get();

        $held = $this->heldClues($request->user())->groupBy('investigation_id');

        return view('investigations.index', [
            'investigations' => $investigations,
            'held' => $held,
        ]);
    }

    public function show(Request $request, Investigation $investigation): View
    {
        $isPreview = $this->ensureVisible($request, $investigation->is_published, 'enquetes.gerer');
        $user = $request->user();

        $investigation->load(['clues', 'location']);
        $mine = $this->heldClues($user)->where('investigation_id', $investigation->getKey())->keyBy('id');

        return view('investigations.show', [
            'investigation' => $investigation,
            'location' => $investigation->location?->is_published ? $investigation->location : null,
            'publicClues' => $investigation->clues->where('is_public', true)->values(),
            'privateClues' => $investigation->clues->where('is_public', false)->filter(fn (Clue $clue) => $mine->has($clue->getKey()))->values(),
            'staffClues' => $user?->can('enquetes.gerer') ? $investigation->clues->where('is_public', false)->reject(fn (Clue $clue) => $mine->has($clue->getKey()))->values() : collect(),
            'isPreview' => $isPreview,
        ]);
    }

    public function clue(Request $request, Investigation $investigation, Clue $clue): View
    {
        abort_unless($clue->investigation_id === $investigation->getKey() && $clue->isReadableBy($request->user()), 404);

        $holders = $clue->holdersOwnedBy($request->user())->filter(fn (Character $character) => $character->status === CharacterStatus::Accepted)->values();
        $canShare = $clue->is_shareable && $investigation->is_published && $holders->isNotEmpty();

        return view('investigations.clue', [
            'investigation' => $investigation,
            'clue' => $clue,
            'holders' => $holders,
            'canShare' => $canShare,
            'recipients' => $canShare
                ? Character::query()->accepted()->whereNotIn('id', $clue->characters()->pluck('characters.id'))->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'slug'])
                : collect(),
        ]);
    }

    public function share(Request $request, Investigation $investigation, Clue $clue, ClueService $clues): RedirectResponse
    {
        abort_unless($clue->investigation_id === $investigation->getKey() && $investigation->is_published, 404);
        abort_unless($clue->is_shareable, 403);

        $data = $request->validate([
            'from_character_id' => ['required', 'integer'],
            'to_character_id' => ['required', 'integer'],
        ], [], ['from_character_id' => 'personnage', 'to_character_id' => 'destinataire']);

        $from = $clue->holdersOwnedBy($request->user())->firstWhere('id', (int) $data['from_character_id']);
        abort_if($from === null || $from->status !== CharacterStatus::Accepted, 403);

        $to = Character::query()->accepted()->with('user')->find((int) $data['to_character_id']);

        if ($to === null) {
            throw ValidationException::withMessages(['to_character_id' => 'Choisissez un personnage accepté.']);
        }

        if ($to->user && UserBlock::between($request->user(), $to->user)) {
            throw ValidationException::withMessages(['to_character_id' => 'Un blocage existe entre vos deux comptes.']);
        }

        $shared = $clues->share($clue, $from, $to);

        return redirect()->route('clues.show', [$investigation, $clue])->with('status', $shared
            ? $from->fullName().' a confié cet indice à '.$to->fullName().'.'
            : $to->fullName().' détenait déjà cet indice.');
    }

    public function mine(Request $request): View
    {
        $clues = Clue::query()
            ->whereHas('characters', fn ($query) => $query->where('characters.user_id', $request->user()->getKey()))
            ->whereHas('investigation', fn ($query) => $query->where('is_published', true))
            ->with(['investigation', 'characters' => fn ($query) => $query->where('characters.user_id', $request->user()->getKey())])
            ->orderBy('investigation_id')
            ->orderBy('sort_order')
            ->get();

        return view('investigations.mine', [
            'groups' => $clues->groupBy('investigation_id'),
        ]);
    }

    /**
     * Indices détenus par les personnages de ce joueur, dans les enquêtes publiées.
     *
     * @return Collection<int, Clue>
     */
    private function heldClues(?User $user): Collection
    {
        if ($user === null) {
            return collect();
        }

        return Clue::query()
            ->whereHas('characters', fn ($query) => $query->where('characters.user_id', $user->getKey()))
            ->with(['characters' => fn ($query) => $query->where('characters.user_id', $user->getKey())])
            ->get();
    }
}
