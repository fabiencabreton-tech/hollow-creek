<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Enums\ClaimStatus;
use App\Enums\VictimStatus;
use App\Models\Character;
use App\Models\Family;
use App\Models\FaceclaimClaim;
use App\Models\KirisawaVictim;
use App\Models\Species;
use App\Services\CharacterWorkflow;
use App\Services\FaceclaimRegistry;
use App\Support\ImageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * « Mes personnages » et la fiche en six étapes. Chaque étape enregistre le brouillon.
 */
class MyCharactersController extends Controller
{
    public const STEPS = [
        1 => 'Identité',
        2 => 'Nature',
        3 => 'Avatar',
        4 => 'Histoire',
        5 => 'Personnalité',
        6 => 'Envoi',
    ];

    public const LAST_STEP = 6;

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

        $character->load(['species', 'family', 'faceclaim', 'predefined', 'reviews', 'traits', 'kirisawaVictim']);

        return view('characters.edit', [
            'character' => $character,
            'step' => $step,
            'steps' => self::STEPS,
            'natures' => Species::query()->published()->natures()->ordered()->get(),
            'traits' => Species::query()->published()->traits()->ordered()->get(),
            'victims' => KirisawaVictim::query()
                ->published()
                ->where(fn ($query) => $query->where('status', VictimStatus::Open->value)->orWhere('id', $character->kirisawa_victim_id))
                ->orderBy('number')
                ->get(),
            'families' => Family::query()->published()
                ->where(fn ($query) => $query->where('is_open', true)->orWhere('id', $character->family_id))
                ->orderByDesc('is_major')->ordered()->get(),
            'reservations' => FaceclaimClaim::query()
                ->with('faceclaim')
                ->where('user_id', $request->user()->getKey())
                ->whereNull('character_id')
                ->where('status', ClaimStatus::Reserved->value)
                ->get(),
            'missing' => $step === self::LAST_STEP ? $character->missingForSubmission() : [],
            'lastReview' => $character->reviews->first(),
        ]);
    }

    public function update(Request $request, Character $character, int $step): RedirectResponse
    {
        Gate::authorize('update', $character);

        match ($step) {
            1 => $this->saveIdentity($request, $character),
            2 => $this->saveNature($request, $character),
            3 => $this->saveAvatar($request, $character),
            4 => $this->saveText($request, $character, 'history', 30000),
            5 => $this->saveText($request, $character, 'personality', 20000),
            default => abort(404),
        };

        $next = $request->input('action') === 'continuer' ? min(self::LAST_STEP, $step + 1) : $step;

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
     * Personnalisation du profil public d'un personnage accepté.
     */
    public function editProfile(Request $request, Character $character): View
    {
        abort_unless($request->user()->is($character->user) && $character->status === CharacterStatus::Accepted, 403);

        return view('characters.profile-edit', ['character' => $character]);
    }

    public function updateProfile(Request $request, Character $character): RedirectResponse
    {
        abort_unless($request->user()->is($character->user) && $character->status === CharacterStatus::Accepted, 403);

        $data = $request->validate([
            'banner' => ['nullable', 'file', 'image', 'mimes:jpeg,png,webp,gif', 'max:2048'],
            'banner_credit' => [Rule::requiredIf(fn () => $request->hasFile('banner') || ($character->banner_path && ! $request->boolean('remove_banner'))), 'nullable', 'string', 'max:255'],
            'accent_color' => ['nullable', Rule::in(array_keys(config('hollow.profiles.accent_colors')))],
            'profile_layout' => ['required', Rule::in(array_keys(config('hollow.profiles.layouts')))],
            'absence_note' => ['nullable', 'string', 'max:200'],
            'absence_until' => ['nullable', 'date', 'after_or_equal:today'],
        ], [], [
            'banner' => 'bannière',
            'banner_credit' => 'crédit de la bannière',
            'accent_color' => 'couleur d’accent',
            'profile_layout' => 'mise en page',
            'absence_note' => 'message d’absence',
            'absence_until' => 'date de retour',
        ]);

        $attributes = [
            'accent_color' => $data['accent_color'] ?? null,
            'profile_layout' => $data['profile_layout'],
            'absence_note' => filled($data['absence_note'] ?? null) ? trim($data['absence_note']) : null,
            'absence_until' => filled($data['absence_note'] ?? null) ? ($data['absence_until'] ?? null) : null,
            'banner_credit' => filled($data['banner_credit'] ?? null) ? trim($data['banner_credit']) : null,
        ];

        if ($request->boolean('remove_banner')) {
            $attributes['banner_path'] = null;
            $attributes['banner_credit'] = null;
        }

        if ($request->hasFile('banner')) {
            $attributes['banner_path'] = app(ImageStore::class)->store($request->file('banner'), 'bannieres', 1600);
        }

        $character->forceFill($attributes)->save();

        return redirect()->route('characters.show', $character)->with('status', 'Profil mis à jour.');
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
            'family_id' => ['nullable', Rule::exists('families', 'id')->where('is_published', true)->where(
                fn ($query) => $query->where('is_open', true)->orWhere('id', (int) $character->family_id)
            )],
            'profession' => ['required', 'string', 'max:120'],
            'quote' => ['nullable', 'string', 'max:300'],
        ], [
            'age.min' => 'Les personnages de Tsukimori sont majeurs.',
            'apparent_age.min' => 'L’âge apparent doit lui aussi être d’au moins 18 ans.',
        ], [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'age' => 'âge',
            'apparent_age' => 'âge apparent',
            'family_id' => 'famille',
            'profession' => 'profession',
            'quote' => 'citation',
        ]);

        // Un Revenu garde le nom de la victime de Kirisawa qu'il incarne.
        $victim = $character->kirisawaVictim;

        if ($victim && (filled($victim->first_name) || filled($victim->last_name))) {
            $data['first_name'] = $victim->first_name ?: $data['first_name'];
            $data['last_name'] = $victim->last_name;
        }

        // Tant que la fiche n'est pas acceptée, son adresse suit le nom choisi.
        $character->setAttribute('slug', null);
        $character->fill($data)->save();
    }

    /**
     * Étape « Nature » : une nature, des spécialisations ou conditions compatibles, et
     * les champs propres à chaque catégorie (le reste est vidé pour rester cohérent).
     */
    private function saveNature(Request $request, Character $character): void
    {
        $data = $request->validate([
            'species_id' => ['required', 'integer'],
            'traits' => ['nullable', 'array'],
            'traits.*' => ['integer'],
            'kirisawa_victim_id' => ['nullable', 'integer'],
            'death_date' => ['nullable', 'date', 'before_or_equal:today'],
            'death_circumstances' => ['nullable', 'string', 'max:3000'],
            'anchor' => ['nullable', 'string', 'max:2000'],
            'emotion' => ['nullable', 'string', 'max:2000'],
            'occult_specialties' => ['nullable', 'array', 'max:'.config('hollow.characters.max_occult_specialties', 2)],
            'occult_specialties.*' => [Rule::in(array_keys(config('hollow.characters.occult_specialties')))],
            'curse_origin' => ['nullable', 'string', 'max:3000'],
            'curse_effects' => ['nullable', 'string', 'max:3000'],
            'curse_resolution' => ['nullable', 'string', 'max:3000'],
            'abilities' => ['nullable', 'string', 'max:5000'],
            'weaknesses' => ['nullable', 'string', 'max:5000'],
        ], [
            'occult_specialties.max' => 'Un praticien ne maîtrise pas toutes les spécialisations : deux au plus.',
        ], [
            'species_id' => 'nature',
            'death_date' => 'date de décès',
            'kirisawa_victim_id' => 'victime du registre',
        ]);

        $nature = Species::query()->published()->natures()->find($data['species_id']);

        if ($nature === null) {
            throw ValidationException::withMessages(['species_id' => 'Choisissez une nature.']);
        }

        $traits = Species::query()->published()->traits()->whereIn('id', $data['traits'] ?? [])->get();

        foreach ($traits as $trait) {
            if (! $trait->allowsNature($nature)) {
                throw ValidationException::withMessages(['traits' => '« '.$trait->name.' » ne s’applique pas à la nature « '.$nature->name.' ».']);
            }
        }

        $victim = null;

        if ($nature->needs_registry && filled($data['kirisawa_victim_id'] ?? null)) {
            $victim = KirisawaVictim::query()->published()
                ->where(fn ($query) => $query->where('status', VictimStatus::Open->value)->orWhere('id', $character->kirisawa_victim_id))
                ->find($data['kirisawa_victim_id']);

            if ($victim === null) {
                throw ValidationException::withMessages(['kirisawa_victim_id' => 'Cette victime n’est pas ouverte aux joueurs.']);
            }
        }

        $isSpirit = in_array($nature->slug, ['yurei', 'onryo'], true);
        $isPractitioner = $traits->contains('slug', 'praticiens-de-l-occulte');
        $isCursed = $traits->contains('slug', 'maudits');

        $attributes = [
            'species_id' => $nature->getKey(),
            'death_date' => $nature->needs_death_date ? ($victim ? '1996-10-31' : ($data['death_date'] ?? null)) : null,
            'death_circumstances' => $nature->needs_death_date ? ($data['death_circumstances'] ?? null) : null,
            'anchor' => $isSpirit ? ($data['anchor'] ?? null) : null,
            'emotion' => $nature->slug === 'onryo' ? ($data['emotion'] ?? null) : null,
            'occult_specialties' => $isPractitioner ? array_values(array_unique($data['occult_specialties'] ?? [])) : null,
            'curse_origin' => $isCursed ? ($data['curse_origin'] ?? null) : null,
            'curse_effects' => $isCursed ? ($data['curse_effects'] ?? null) : null,
            'curse_resolution' => $isCursed ? ($data['curse_resolution'] ?? null) : null,
            'abilities' => $data['abilities'] ?? null,
            'weaknesses' => $data['weaknesses'] ?? null,
        ];

        DB::transaction(function () use ($character, $attributes, $traits, $victim): void {
            // Changer de victime libère celle qui était réservée pour cette fiche.
            if ($character->kirisawa_victim_id && $character->kirisawa_victim_id !== $victim?->getKey()) {
                KirisawaVictim::query()
                    ->whereKey($character->kirisawa_victim_id)
                    ->where('character_id', $character->getKey())
                    ->update(['status' => VictimStatus::Open->value, 'character_id' => null]);
            }

            $character->forceFill($attributes);
            $character->forceFill(['kirisawa_victim_id' => $victim?->getKey()]);

            // Un Revenu porte le nom de la victime qu'il incarne.
            if ($victim && (filled($victim->first_name) || filled($victim->last_name))) {
                $character->setAttribute('slug', null);
                $character->forceFill(['first_name' => $victim->first_name ?: $character->first_name, 'last_name' => $victim->last_name]);
            }

            $character->save();
            $character->traits()->sync($traits->modelKeys());
        });
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
