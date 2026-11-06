<?php

namespace App\Http\Controllers;

use App\Enums\AdCategory;
use App\Enums\CharacterStatus;
use App\Models\Character;
use App\Models\ClassifiedAd;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Petites annonces des habitants : chacune est signée par un personnage et expire
 * d'elle-même.
 */
class ClassifiedAdController extends Controller
{
    public function index(Request $request): View
    {
        $category = AdCategory::tryFrom((string) $request->query('rubrique', ''));
        $user = $request->user();

        return view('ads.index', [
            'ads' => ClassifiedAd::query()
                ->active()
                ->with(['character', 'author'])
                ->when($category, fn ($query) => $query->where('category', $category->value))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'category' => $category,
            'categories' => AdCategory::cases(),
            'myCharacters' => $user ? Character::query()->where('user_id', $user->getKey())->accepted()->orderBy('first_name')->get() : collect(),
            'myAds' => $user ? ClassifiedAd::query()->where('user_id', $user->getKey())->latest('id')->limit(20)->get() : collect(),
            'durations' => ClassifiedAd::DURATIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $character = $this->ownCharacter($request, (int) $data['character_id']);
        $user = $request->user();

        if (ClassifiedAd::query()->where('user_id', $user->getKey())->active()->count() >= ClassifiedAd::MAX_ACTIVE_PER_USER) {
            throw ValidationException::withMessages(['title' => 'Vous avez déjà '.ClassifiedAd::MAX_ACTIVE_PER_USER.' annonces en ligne : retirez-en une avant d’en publier une autre.']);
        }

        $ad = new ClassifiedAd;
        $ad->forceFill([
            'user_id' => $user->getKey(),
            'character_id' => $character->getKey(),
            'character_name' => $character->fullName(),
            'category' => $data['category'],
            'title' => trim($data['title']),
            'body' => trim($data['body']),
            'contact' => filled($data['contact'] ?? null) ? trim($data['contact']) : null,
            'expires_at' => now()->addDays((int) $data['duration']),
        ])->save();

        return redirect()->to(route('ads.index').'#annonce-'.$ad->getKey())->with('status', 'Annonce publiée jusqu’au '.$ad->expires_at->locale('fr')->isoFormat('LL').'.');
    }

    public function renew(Request $request, ClassifiedAd $ad): RedirectResponse
    {
        Gate::authorize('update', $ad);

        $data = $request->validate(['duration' => ['required', 'integer', Rule::in(ClassifiedAd::DURATIONS)]], [], ['duration' => 'durée']);

        if (! $ad->isActive() && ClassifiedAd::query()->where('user_id', $request->user()->getKey())->active()->count() >= ClassifiedAd::MAX_ACTIVE_PER_USER) {
            throw ValidationException::withMessages(['duration' => 'Vous avez déjà '.ClassifiedAd::MAX_ACTIVE_PER_USER.' annonces en ligne.']);
        }

        $ad->forceFill(['expires_at' => now()->addDays((int) $data['duration'])])->save();

        return redirect()->route('ads.index')->with('status', 'Annonce prolongée jusqu’au '.$ad->expires_at->locale('fr')->isoFormat('LL').'.');
    }

    public function destroy(Request $request, ClassifiedAd $ad): RedirectResponse
    {
        Gate::authorize('delete', $ad);

        $ad->delete();

        return redirect()->route('ads.index')->with('status', 'Annonce retirée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'character_id' => ['required', 'integer'],
            'category' => ['required', Rule::enum(AdCategory::class)],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:1500'],
            'contact' => ['nullable', 'string', 'max:200'],
            'duration' => ['required', 'integer', Rule::in(ClassifiedAd::DURATIONS)],
        ], [], [
            'character_id' => 'personnage',
            'category' => 'rubrique',
            'title' => 'titre',
            'body' => 'texte',
            'contact' => 'pour répondre',
            'duration' => 'durée',
        ]);
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
