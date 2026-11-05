<?php

namespace App\Services;

use App\Enums\CharacterStatus;
use App\Enums\ClaimStatus;
use App\Enums\PredefinedStatus;
use App\Models\Character;
use App\Models\CharacterReview;
use App\Models\PredefinedCharacter;
use App\Models\User;
use App\Notifications\CharacterReviewed;
use App\Support\ActivityLogger;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cycle de vie d'une fiche : brouillon → en attente → acceptée, refusée ou modifications demandées.
 */
class CharacterWorkflow
{
    public function __construct(
        private readonly Settings $settings,
        private readonly FaceclaimRegistry $registry,
    ) {}

    public function remainingSlots(User $user): int
    {
        $used = Character::query()->where('user_id', $user->getKey())->countingTowardsLimit()->count();

        return max(0, $this->settings->maxCharacters() - $used);
    }

    /**
     * Nouveau brouillon, éventuellement pré-rempli depuis un prédéfini.
     */
    public function start(User $user, ?PredefinedCharacter $predefined = null): Character
    {
        if ($this->remainingSlots($user) < 1) {
            throw ValidationException::withMessages([
                'character' => 'Vous avez atteint le nombre maximum de personnages ('.$this->settings->maxCharacters().').',
            ]);
        }

        if ($predefined && (! $predefined->is_published || ! $predefined->isOpen())) {
            throw ValidationException::withMessages(['character' => 'Ce rôle n’est plus disponible.']);
        }

        $character = new Character([
            'first_name' => $predefined?->first_name ?: 'Nouveau personnage',
            'last_name' => $predefined?->last_name,
            'species_id' => $predefined?->species_id,
            'family_id' => $predefined?->family_id,
        ]);

        $character->forceFill([
            'user_id' => $user->getKey(),
            'status' => CharacterStatus::Draft,
            'predefined_character_id' => $predefined?->getKey(),
        ])->save();

        return $character;
    }

    /**
     * Envoi de la fiche à l'équipe.
     */
    public function submit(Character $character): void
    {
        if (! $character->isEditableByOwner()) {
            throw ValidationException::withMessages(['character' => 'Cette fiche a déjà été envoyée.']);
        }

        $missing = $character->missingForSubmission();

        if ($missing !== []) {
            throw ValidationException::withMessages(['character' => $missing]);
        }

        DB::transaction(function () use ($character): void {
            if ($character->predefined_character_id) {
                $predefined = PredefinedCharacter::query()->lockForUpdate()->find($character->predefined_character_id);

                $isOurs = $predefined && $predefined->character_id === $character->getKey();

                if (! $predefined || (! $predefined->isOpen() && ! $isOurs)) {
                    throw ValidationException::withMessages(['character' => 'Ce rôle prédéfini est déjà en examen ou attribué à un autre joueur.']);
                }

                $predefined->forceFill([
                    'status' => PredefinedStatus::UnderReview,
                    'character_id' => $character->getKey(),
                ])->save();
            }

            // La célébrité reste de côté pendant l'examen.
            $character->activeClaim()->update(['expires_at' => null]);

            $character->forceFill([
                'status' => CharacterStatus::Pending,
                'submitted_at' => now(),
            ])->save();
        });
    }

    public function accept(Character $character, User $staff, ?string $comment = null): void
    {
        $this->ensurePending($character);

        DB::transaction(function () use ($character, $staff, $comment): void {
            $character->activeClaim()->update([
                'status' => ClaimStatus::Taken->value,
                'expires_at' => null,
                'decided_by' => $staff->getKey(),
                'decided_at' => now(),
            ]);

            if ($character->predefined) {
                $character->predefined->forceFill(['status' => PredefinedStatus::Taken, 'character_id' => $character->getKey()])->save();
            }

            $character->forceFill(['status' => CharacterStatus::Accepted, 'accepted_at' => now()])->save();

            $this->record($character, $staff, CharacterStatus::Accepted, $comment);
        });

        ActivityLogger::record('fiche.acceptee', $character, $character->fullName(), causer: $staff);
        $this->notifyOwner($character, CharacterStatus::Accepted, 'Fiche acceptée : '.$character->fullName(), $comment);
    }

    public function requestChanges(Character $character, User $staff, string $comment): void
    {
        $this->ensurePending($character);

        DB::transaction(function () use ($character, $staff, $comment): void {
            $character->forceFill(['status' => CharacterStatus::ChangesRequested])->save();
            $this->record($character, $staff, CharacterStatus::ChangesRequested, $comment);
        });

        ActivityLogger::record('fiche.modifications', $character, $character->fullName(), causer: $staff);
        $this->notifyOwner($character, CharacterStatus::ChangesRequested, 'Modifications demandées : '.$character->fullName(), $comment);
    }

    public function refuse(Character $character, User $staff, string $comment): void
    {
        $this->ensurePending($character);

        DB::transaction(function () use ($character, $staff, $comment): void {
            $this->freeResources($character, $staff, 'Fiche refusée');
            $character->forceFill(['status' => CharacterStatus::Refused])->save();
            $this->record($character, $staff, CharacterStatus::Refused, $comment);
        });

        ActivityLogger::record('fiche.refusee', $character, $character->fullName(), causer: $staff);
        $this->notifyOwner($character, CharacterStatus::Refused, 'Fiche refusée : '.$character->fullName(), $comment);
    }

    /**
     * Un personnage accepté est archivé : sa célébrité redevient disponible.
     */
    public function archive(Character $character, User $by, ?string $comment = null): void
    {
        DB::transaction(function () use ($character, $by, $comment): void {
            $this->freeResources($character, $by, 'Personnage archivé');
            $character->forceFill(['status' => CharacterStatus::Archived])->save();
            $this->record($character, $by, CharacterStatus::Archived, $comment);
        });

        ActivityLogger::record('fiche.archivee', $character, $character->fullName(), causer: $by);
    }

    /**
     * Le joueur abandonne un brouillon.
     */
    public function discardDraft(Character $character): void
    {
        if (! $character->isEditableByOwner()) {
            throw ValidationException::withMessages(['character' => 'Seul un brouillon peut être supprimé.']);
        }

        DB::transaction(function () use ($character): void {
            $this->freeResources($character, null, 'Brouillon supprimé');
            $character->delete();
        });
    }

    private function notifyOwner(Character $character, CharacterStatus $decision, string $title, ?string $comment): void
    {
        if ($character->user) {
            app(Notifier::class)->send(
                $character->user,
                'fiche',
                $title,
                route('characters.mine', absolute: false),
                $comment,
                new CharacterReviewed($character, $decision, $comment),
            );
        }
    }

    /**
     * Libère l'avatar et le prédéfini d'un personnage, quel que soit son statut
     * (suppression d'un compte).
     */
    public function releaseResources(Character $character, ?User $by, string $note): void
    {
        $this->freeResources($character, $by, $note);
    }

    private function freeResources(Character $character, ?User $by, string $note): void
    {
        $claim = $character->activeClaim()->first();

        if ($claim) {
            $this->registry->release($claim, $by, $note);
        }

        $predefined = $character->predefined;

        if ($predefined && $predefined->character_id === $character->getKey()) {
            $predefined->forceFill(['status' => PredefinedStatus::Open, 'character_id' => null])->save();
        }
    }

    private function ensurePending(Character $character): void
    {
        if ($character->status !== CharacterStatus::Pending) {
            throw ValidationException::withMessages(['character' => 'Cette fiche n’est pas en attente d’examen.']);
        }
    }

    private function record(Character $character, User $staff, CharacterStatus $decision, ?string $comment): void
    {
        CharacterReview::query()->create([
            'character_id' => $character->getKey(),
            'user_id' => $staff->getKey(),
            'decision' => $decision,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }
}
