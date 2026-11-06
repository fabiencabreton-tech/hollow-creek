<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Enums\PrelinkRelation;
use App\Enums\PrelinkStatus;
use App\Models\Character;
use App\Models\Family;
use App\Models\FamilyPrelink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Préliens familiaux : un membre cherche un frère, une cousine, un parent…
 */
class FamilyPrelinkController extends Controller
{
    public function store(Request $request, Family $family): RedirectResponse
    {
        abort_unless($family->is_published, 404);

        $data = $request->validate([
            'character_id' => ['required', 'integer'],
            'relation' => ['required', Rule::enum(PrelinkRelation::class)],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:30', 'max:4000'],
            'age_range' => ['nullable', 'string', 'max:40'],
            'faceclaims' => ['nullable', 'string', 'max:200'],
        ], [], [
            'character_id' => 'personnage',
            'relation' => 'lien recherché',
            'title' => 'titre',
            'description' => 'description',
            'age_range' => 'âge',
            'faceclaims' => 'avatars suggérés',
        ]);

        $character = Character::query()->find((int) $data['character_id']);

        if ($character === null || $character->user_id !== $request->user()->getKey() || $character->status !== CharacterStatus::Accepted || $character->family_id !== $family->getKey()) {
            throw ValidationException::withMessages(['character_id' => 'Seul un membre accepté de cette famille peut proposer un prélien.']);
        }

        if (FamilyPrelink::query()->where('character_id', $character->getKey())->open()->count() >= FamilyPrelink::MAX_OPEN_PER_CHARACTER) {
            throw ValidationException::withMessages(['title' => $character->fullName().' a déjà '.FamilyPrelink::MAX_OPEN_PER_CHARACTER.' préliens ouverts.']);
        }

        $prelink = new FamilyPrelink;
        $prelink->forceFill([
            'family_id' => $family->getKey(),
            'character_id' => $character->getKey(),
            'user_id' => $request->user()->getKey(),
            'relation' => $data['relation'],
            'title' => trim($data['title']),
            'description' => trim($data['description']),
            'age_range' => filled($data['age_range'] ?? null) ? trim($data['age_range']) : null,
            'faceclaims' => filled($data['faceclaims'] ?? null) ? trim($data['faceclaims']) : null,
            'status' => PrelinkStatus::Open,
        ])->save();

        return redirect()->to(route('families.show', $family).'#prelien-'.$prelink->getKey())->with('status', 'Prélien publié.');
    }

    public function fill(Request $request, FamilyPrelink $prelink): RedirectResponse
    {
        abort_unless($prelink->user_id === $request->user()->getKey(), 403);

        $data = $request->validate(['taken_character_id' => ['nullable', 'integer']], [], ['taken_character_id' => 'personnage']);
        $taken = isset($data['taken_character_id']) ? Character::query()->accepted()->find((int) $data['taken_character_id']) : null;

        $prelink->forceFill(['status' => PrelinkStatus::Taken, 'taken_character_id' => $taken?->getKey()])->save();

        return redirect()->route('families.show', $prelink->family)->with('status', 'Prélien marqué comme pourvu.');
    }

    public function destroy(Request $request, FamilyPrelink $prelink): RedirectResponse
    {
        abort_unless($prelink->user_id === $request->user()->getKey() || $request->user()->can('signalements.traiter'), 403);

        $family = $prelink->family;
        $prelink->delete();

        return redirect()->route('families.show', $family)->with('status', 'Prélien retiré.');
    }
}
