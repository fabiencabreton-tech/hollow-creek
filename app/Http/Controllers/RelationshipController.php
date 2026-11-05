<?php

namespace App\Http\Controllers;

use App\Enums\RelationshipKind;
use App\Enums\RelationshipStatus;
use App\Models\Character;
use App\Models\Relationship;
use App\Services\RelationshipService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * « Relations » : propositions reçues et envoyées, liens actifs.
 */
class RelationshipController extends Controller
{
    public function __construct(private readonly RelationshipService $relationships) {}

    public function index(Request $request): View
    {
        $ids = Character::query()->where('user_id', $request->user()->getKey())->pluck('id');

        $base = Relationship::query()->with(['character.species', 'target.species']);

        return view('relationships.index', [
            'received' => (clone $base)->whereIn('target_character_id', $ids)->where('status', RelationshipStatus::Proposed->value)->latest()->get(),
            'sent' => (clone $base)->whereIn('character_id', $ids)->where('status', RelationshipStatus::Proposed->value)->latest()->get(),
            'active' => (clone $base)->where('status', RelationshipStatus::Accepted->value)
                ->where(fn ($query) => $query->whereIn('character_id', $ids)->orWhereIn('target_character_id', $ids))
                ->latest('responded_at')->get(),
            'myIds' => $ids->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'character_id' => ['required', 'integer'],
            'target_character_id' => ['required', 'integer'],
            'kind' => ['required', Rule::enum(RelationshipKind::class)],
            'label' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'character_id' => 'votre personnage',
            'kind' => 'type de lien',
            'label' => 'libellé',
            'description' => 'description',
        ]);

        $this->relationships->propose(
            $request->user(),
            Character::query()->findOrFail($data['character_id']),
            Character::query()->with('user')->findOrFail($data['target_character_id']),
            RelationshipKind::from($data['kind']),
            $data['label'] ?? null,
            $data['description'] ?? null,
        );

        return redirect()->route('relationships.index')->with('status', 'Proposition envoyée : le lien apparaîtra sur les deux fiches une fois accepté.');
    }

    public function accept(Request $request, Relationship $relationship): RedirectResponse
    {
        $data = $request->validate(['description' => ['nullable', 'string', 'max:2000']]);

        $this->relationships->accept($relationship, $request->user(), $data['description'] ?? null);

        return redirect()->route('relationships.index')->with('status', 'Lien accepté.');
    }

    public function decline(Request $request, Relationship $relationship): RedirectResponse
    {
        $this->relationships->decline($relationship, $request->user());

        return redirect()->route('relationships.index')->with('status', 'Proposition déclinée.');
    }

    public function withdraw(Request $request, Relationship $relationship): RedirectResponse
    {
        $this->relationships->withdraw($relationship, $request->user());

        return redirect()->route('relationships.index')->with('status', 'Proposition retirée.');
    }

    public function end(Request $request, Relationship $relationship): RedirectResponse
    {
        $this->relationships->end($relationship, $request->user());

        return redirect()->route('relationships.index')->with('status', 'Le lien est terminé ; il n’apparaît plus sur les fiches.');
    }

    public function describe(Request $request, Relationship $relationship): RedirectResponse
    {
        $data = $request->validate(['description' => ['nullable', 'string', 'max:2000']]);

        $this->relationships->describe($relationship, $request->user(), $data['description'] ?? null);

        return redirect()->route('relationships.index')->with('status', 'Description enregistrée.');
    }
}
