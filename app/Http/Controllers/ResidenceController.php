<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Http\Controllers\Concerns\ChecksPreview;
use App\Models\Character;
use App\Models\District;
use App\Models\Residence;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Habitations : où vivent les personnages. On emménage seul dans une habitation
 * ouverte qui a encore de la place ; les autres passent par l'équipe.
 */
class ResidenceController extends Controller
{
    use ChecksPreview;

    public function index(): View
    {
        $residences = Residence::query()
            ->published()
            ->withCount('residents')
            ->with('district')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $districts = District::query()->where('is_published', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('residences.index', [
            'groups' => $districts
                ->map(fn (District $district) => ['district' => $district, 'residences' => $residences->where('district_id', $district->getKey())->values()])
                ->filter(fn (array $group) => $group['residences']->isNotEmpty())
                ->values(),
            'others' => $residences->filter(fn (Residence $residence) => $residence->district === null || ! $residence->district->is_published)->values(),
        ]);
    }

    public function show(Request $request, Residence $residence): View
    {
        $isPreview = $this->ensureVisible($request, $residence->is_published, 'univers.gerer');
        $user = $request->user();

        $residence->load(['district', 'residents']);

        $myCharacters = $user
            ? Character::query()->where('user_id', $user->getKey())->accepted()->with('residence')->orderBy('first_name')->get()
            : collect();

        return view('residences.show', [
            'residence' => $residence,
            'district' => $residence->district?->is_published ? $residence->district : null,
            'freePlaces' => $residence->freePlaces(),
            'candidates' => $myCharacters->filter(fn (Character $character) => $character->residence_id !== $residence->getKey())->values(),
            'livingHere' => $myCharacters->filter(fn (Character $character) => $character->residence_id === $residence->getKey())->values(),
            'isPreview' => $isPreview,
        ]);
    }

    public function moveIn(Request $request, Residence $residence): RedirectResponse
    {
        abort_unless($residence->is_published, 404);

        $data = $request->validate(['character_id' => ['required', 'integer']], [], ['character_id' => 'personnage']);
        $character = $this->ownCharacter($request, (int) $data['character_id']);

        if (! $residence->is_open) {
            throw ValidationException::withMessages(['character_id' => 'Cette habitation n’est pas libre : écrivez à l’équipe pour y emménager.']);
        }

        DB::transaction(function () use ($residence, $character): void {
            // Verrou : deux emménagements simultanés ne dépassent pas la capacité.
            $locked = Residence::query()->whereKey($residence->getKey())->lockForUpdate()->firstOrFail();

            if (Character::query()->where('residence_id', $locked->getKey())->whereKeyNot($character->getKey())->count() >= $locked->capacity) {
                throw ValidationException::withMessages(['character_id' => 'Il n’y a plus de place dans cette habitation.']);
            }

            $character->forceFill(['residence_id' => $locked->getKey()])->save();
        });

        return redirect()->route('residences.show', $residence)->with('status', $character->fullName().' habite désormais ici.');
    }

    public function moveOut(Request $request, Character $character): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->getKey(), 403);

        $residence = $character->residence;
        $character->forceFill(['residence_id' => null])->save();

        return $residence
            ? redirect()->route('residences.show', $residence)->with('status', $character->fullName().' a déménagé.')
            : redirect()->route('characters.mine');
    }

    private function ownCharacter(Request $request, int $id): Character
    {
        $character = Character::query()->find($id);

        if ($character === null || $character->user_id !== $request->user()->getKey() || $character->status !== CharacterStatus::Accepted) {
            throw ValidationException::withMessages(['character_id' => 'Choisissez l’un de vos personnages acceptés.']);
        }

        return $character;
    }
}
