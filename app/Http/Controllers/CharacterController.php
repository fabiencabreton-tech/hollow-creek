<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Enums\ParticipantStatus;
use App\Enums\SceneStatus;
use App\Models\Character;
use App\Models\Scene;
use App\Models\Family;
use App\Models\Species;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Trombinoscope et profils publics des personnages acceptés.
 */
class CharacterController extends Controller
{
    public function index(Request $request): View
    {
        $species = Species::query()->published()->ordered()->get();
        $families = Family::query()->published()->ordered()->get();

        $speciesFilter = $species->firstWhere('slug', (string) $request->query('espece'));
        $familyFilter = $families->firstWhere('slug', (string) $request->query('famille'));

        $characters = Character::query()
            ->accepted()
            ->with(['species', 'family'])
            ->when($speciesFilter, fn ($query) => $query->where('species_id', $speciesFilter->getKey()))
            ->when($familyFilter, fn ($query) => $query->where('family_id', $familyFilter->getKey()))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(24)
            ->withQueryString();

        return view('characters.index', [
            'characters' => $characters,
            'species' => $species,
            'families' => $families,
            'speciesFilter' => $speciesFilter,
            'familyFilter' => $familyFilter,
        ]);
    }

    public function show(Request $request, Character $character): View
    {
        abort_unless($request->user()?->can('view', $character) ?? $character->status === CharacterStatus::Accepted, 404);

        $character->load(['species', 'family', 'faceclaim', 'user']);

        return view('characters.show', [
            'character' => $character,
            'isPreview' => $character->status !== CharacterStatus::Accepted,
            'scenes' => Scene::query()
                ->public()
                ->whereIn('status', [SceneStatus::Ongoing->value, SceneStatus::Paused->value, SceneStatus::Finished->value])
                ->whereHas('participants', fn ($query) => $query
                    ->where('character_id', $character->getKey())
                    ->whereIn('status', [ParticipantStatus::Accepted->value, ParticipantStatus::Left->value]))
                ->with('location')
                ->latest('last_post_at')
                ->limit(40)
                ->get(),
        ]);
    }
}
