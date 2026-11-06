<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Enums\FamilyRequestStatus;
use App\Models\Character;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyRequest;
use App\Services\CharacterWorkflow;
use App\Services\FamilyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Rejoindre une famille, en proposer une, la quitter, se placer dans l'arbre.
 */
class FamilyMembershipController extends Controller
{
    public function __construct(private readonly FamilyService $families) {}

    /**
     * Nouveau personnage né dans cette famille : la fiche s'ouvre avec la famille déjà choisie.
     */
    public function newCharacter(Request $request, Family $family, CharacterWorkflow $workflow): RedirectResponse
    {
        abort_unless($family->is_published, 404);

        if (! $family->is_open) {
            return back()->withErrors(['family_id' => 'Cette famille n’accueille pas de nouveaux membres pour le moment.']);
        }

        $character = $workflow->start($request->user());
        $character->forceFill(['family_id' => $family->getKey()])->save();

        return redirect()->route('characters.edit', [$character, 1])->with('status', 'Fiche ouverte dans la famille '.$family->shortName().'. L’équipe validera ce lien avec la fiche.');
    }

    public function join(Request $request, Family $family): RedirectResponse
    {
        abort_unless($family->is_published, 404);

        $data = $request->validate([
            'character_id' => ['required', 'integer'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [], ['character_id' => 'personnage', 'message' => 'message']);

        $character = $this->ownAccepted($request, (int) $data['character_id']);
        $this->families->requestJoin($character, $family, $data['message'] ?? null);

        return redirect()->route('families.show', $family)->with('status', 'Demande envoyée : l’équipe vous répondra par une notification.');
    }

    public function propose(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'character_id' => ['required', 'integer'],
            'proposed_name' => ['required', 'string', 'min:2', 'max:60'],
            'proposed_japanese_name' => ['nullable', 'string', 'max:20'],
            'proposed_summary' => ['required', 'string', 'min:30', 'max:2000'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'character_id' => 'personnage',
            'proposed_name' => 'nom de la famille',
            'proposed_japanese_name' => 'nom en japonais',
            'proposed_summary' => 'présentation',
        ]);

        $character = $this->ownAccepted($request, (int) $data['character_id']);
        $this->families->propose($character, $data['proposed_name'], $data['proposed_japanese_name'] ?? null, $data['proposed_summary'], $data['message'] ?? null);

        return redirect()->route('families.index')->with('status', 'Proposition envoyée à l’équipe.');
    }

    public function cancel(Request $request, FamilyRequest $familyRequest): RedirectResponse
    {
        abort_unless($familyRequest->user_id === $request->user()->getKey(), 403);

        $this->families->cancel($familyRequest);

        return back()->with('status', 'Demande annulée.');
    }

    public function leave(Request $request, Character $character): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->getKey(), 403);
        $family = $character->family;

        if ($family === null) {
            return back();
        }

        $this->families->leave($character);

        return redirect()->route('families.show', $family)->with('status', $character->fullName().' ne fait plus partie de cette famille.');
    }

    public function place(Request $request, Family $family, FamilyMember $member): RedirectResponse
    {
        abort_unless($member->family_id === $family->getKey(), 404);
        abort_unless($member->character?->user_id === $request->user()->getKey() && $member->character->status === CharacterStatus::Accepted, 403);

        $data = $request->validate([
            'parent_id' => ['nullable', 'integer'],
            'second_parent_id' => ['nullable', 'integer'],
            'spouse_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:300'],
        ], [], ['parent_id' => 'parent', 'second_parent_id' => 'second parent', 'spouse_id' => 'conjoint', 'note' => 'note']);

        $this->families->placeNode(
            $member,
            isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            isset($data['second_parent_id']) ? (int) $data['second_parent_id'] : null,
            isset($data['spouse_id']) ? (int) $data['spouse_id'] : null,
            $data['note'] ?? null,
        );

        return redirect()->to(route('families.show', $family).'#arbre')->with('status', 'Arbre mis à jour.');
    }

    private function ownAccepted(Request $request, int $id): Character
    {
        $character = Character::query()->find($id);

        if ($character === null || $character->user_id !== $request->user()->getKey() || $character->status !== CharacterStatus::Accepted) {
            throw ValidationException::withMessages(['character_id' => 'Choisissez l’un de vos personnages acceptés.']);
        }

        return $character;
    }
}
