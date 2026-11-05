<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Enums\ClaimStatus;
use App\Models\Character;
use App\Models\Family;
use App\Models\FaceclaimClaim;
use App\Models\Species;
use App\Services\CharacterWorkflow;
use App\Services\FaceclaimRegistry;
use App\Support\ImageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * « Mes personnages » et la fiche en cinq étapes. Chaque étape enregistre le brouillon.
 */
class MyCharactersController extends Controller
{
    public const STEPS = [
        1 => 'Identité',
        2 => 'Avatar',
        3 => 'Histoire',
        4 => 'Personnalité',
        5 => 'Envoi',
    ];

    public function __construct(
        private readonly CharacterWorkflow $workflow,
        private readonly FaceclaimRegistry $registry,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('characters.mine', [
            'characters' => Character::query()
                ->where('user_id', $user->getKey())
                ->with(['species', 'faceclaim', 'reviews'])
                ->orderByRaw("CASE status WHEN 'acceptee' THEN 0 WHEN 'en_attente' THEN 1 WHEN 'modifications_demandees' THEN 2 WHEN 'brouillon' THEN 3 ELSE 4 END")
                ->latest('updated_at')
                ->get(),
            'remaining' => $this->workflow->remainingSlots($user),
            'reservations' => FaceclaimClaim::query()
                ->with('faceclaim')
                ->where('user_id', $user->getKey())
                ->whereNull('character_id')
                ->whereIn('status', [ClaimStatus::Pending->value, ClaimStatus::Reserved->value])
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $character = $this->workflow->start($request->user());

        return redirect()->route('characters.edit', [$character, 1]);
    }

    public function edit(Request $request, Character $character, int $step = 1): View
    {
        Gate::authorize('update', $character);

        abort_unless(array_key_exists($step, self::STEPS), 404);

        $character->load(['species', 'family', 'faceclaim', 'predefined', 'reviews']);

        return view('characters.edit', [
            'character' => $character,
            'step' => $step,
            'steps' => self::STEPS,
            'species' => Species::query()->published()->ordered()->get(),
            'families' => Family::query()->published()->ordered()->get(),
            'reservations' => FaceclaimClaim::query()
                ->with('faceclaim')
                ->where('user_id', $request->user()->getKey())
                ->whereNull('character_id')
                ->where('status', ClaimStatus::Reserved->value)
                ->get(),
            'missing' => $step === 5 ? $character->missingForSubmission() : [],
            'lastReview' => $character->reviews->first(),
        ]);
    }

    public function update(Request $request, Character $character, int $step): RedirectResponse
    {
        Gate::authorize('update', $character);

        match ($step) {
            1 => $this->saveIdentity($request, $character),
            2 => $this->saveAvatar($request, $character),
            3 => $this->saveText($request, $character, 'history', 30000),
            4 => $this->saveText($request, $character, 'personality', 20000),
            default => abort(404),
        };

        $next = $request->input('action') === 'continuer' ? min(5, $step + 1) : $step;

        return redirect()->route('characters.edit', [$character, $next])->with('status', 'Brouillon enregistré.');
    }

    public function submit(Request $request, Character $character): RedirectResponse
    {
        Gate::authorize('update', $character);

        $request->validate([
            'attestation' => ['accepted'],
        ], [
            'attestation.accepted' => 'Merci de confirmer que la fiche est entièrement écrite par vous.',
        ]);

        $this->workflow->submit($character);

        return redirect()->route('characters.mine')->with('status', 'Fiche envoyée : l’équipe vous répondra par e-mail et sur cette page.');
    }

    public function destroy(Request $request, Character $character): RedirectResponse
    {
        Gate::authorize('delete', $character);

        $this->workflow->discardDraft($character);

        return redirect()->route('characters.mine')->with('status', 'Brouillon supprimé.');
    }

    public function archive(Request $request, Character $character): RedirectResponse
    {
        Gate::authorize('archive', $character);

        $this->workflow->archive($character, $request->user());

        return redirect()->route('characters.mine')->with('status', 'Personnage archivé : sa célébrité redevient disponible.');
    }

    /**
     * Couleur de dialogue du personnage dans les scènes.
     */
    public function updateColor(Request $request, Character $character): RedirectResponse
    {
        abort_unless($request->user()->is($character->user) && $character->status !== CharacterStatus::Archived, 403);

        $data = $request->validate([
            'dialogue_color' => ['nullable', Rule::in(array_keys(config('hollow.scenes.dialogue_colors')))],
        ]);

        $character->forceFill(['dialogue_color' => $data['dialogue_color'] ?? null])->save();

        return redirect()->route('characters.mine')->with('status', 'Couleur de dialogue enregistrée.');
    }

    private function saveIdentity(Request $request, Character $character): void
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:60'],
            'last_name' => ['nullable', 'string', 'max:60'],
            'age' => ['required', 'integer', 'min:18', 'max:130'],
            'apparent_age' => ['nullable', 'integer', 'min:18', 'max:130'],
            'species_id' => ['required', Rule::exists('species', 'id')->where('is_published', true)],
            'family_id' => ['nullable', Rule::exists('families', 'id')->where('is_published', true)],
            'profession' => ['required', 'string', 'max:120'],
            'quote' => ['nullable', 'string', 'max:300'],
        ], [
            'age.min' => 'Les personnages de Hollow Creek sont majeurs.',
            'apparent_age.min' => 'L’âge apparent doit lui aussi être d’au moins 18 ans.',
        ], [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'age' => 'âge',
            'apparent_age' => 'âge apparent',
            'species_id' => 'espèce',
            'family_id' => 'famille',
            'profession' => 'profession',
            'quote' => 'citation',
        ]);

        // Tant que la fiche n'est pas acceptée, son adresse suit le nom choisi.
        $character->setAttribute('slug', null);
        $character->fill($data)->save();
    }

    private function saveAvatar(Request $request, Character $character): void
    {
        $request->validate([
            'faceclaim' => ['required', 'string', 'min:3', 'max:120'],
            'avatar' => [$character->avatar_path ? 'nullable' : 'required', 'file', 'image', 'mimes:jpeg,png,webp,gif', 'max:2048'],
            'avatar_credit' => ['required', 'string', 'max:255'],
        ], [], [
            'faceclaim' => 'célébrité',
            'avatar' => 'image de l’avatar',
            'avatar_credit' => 'crédit de l’image',
        ]);

        $this->registry->attachToCharacter($character, (string) $request->input('faceclaim'));

        if ($request->hasFile('avatar')) {
            $path = app(ImageStore::class)->store($request->file('avatar'), 'avatars', 800);
            $character->forceFill(['avatar_path' => $path]);
        }

        $character->forceFill(['avatar_credit' => trim((string) $request->input('avatar_credit'))])->save();
    }

    private function saveText(Request $request, Character $character, string $field, int $max): void
    {
        $data = $request->validate([
            $field => ['nullable', 'string', 'max:'.$max],
        ]);

        $character->forceFill([$field => $data[$field] ?? null])->save();
    }
}
