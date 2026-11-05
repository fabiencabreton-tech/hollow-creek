<?php

namespace App\Services;

use App\Enums\CharacterStatus;
use App\Enums\RelationshipKind;
use App\Enums\RelationshipStatus;
use App\Models\Character;
use App\Models\Relationship;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Validation\ValidationException;

/**
 * Liens entre personnages : proposés, acceptés, déclinés, terminés. Jamais automatiques.
 */
class RelationshipService
{
    public function propose(User $by, Character $mine, Character $target, RelationshipKind $kind, ?string $label, ?string $description): Relationship
    {
        if ($mine->user_id !== $by->getKey() || $mine->status !== CharacterStatus::Accepted) {
            throw ValidationException::withMessages(['character_id' => 'Choisissez l’un de vos personnages acceptés.']);
        }

        if ($target->status !== CharacterStatus::Accepted || $target->user_id === $by->getKey() || $target->user === null) {
            throw ValidationException::withMessages(['target_character_id' => 'Ce personnage ne peut pas recevoir de proposition.']);
        }

        if (UserBlock::between($by, $target->user)) {
            throw ValidationException::withMessages(['target_character_id' => 'Un blocage existe entre vos deux comptes.']);
        }

        $exists = Relationship::query()
            ->whereIn('status', [RelationshipStatus::Proposed->value, RelationshipStatus::Accepted->value])
            ->where(fn ($query) => $query
                ->where(fn ($inner) => $inner->where('character_id', $mine->getKey())->where('target_character_id', $target->getKey()))
                ->orWhere(fn ($inner) => $inner->where('character_id', $target->getKey())->where('target_character_id', $mine->getKey())))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['target_character_id' => 'Un lien existe déjà, ou attend une réponse, entre ces deux personnages.']);
        }

        $relationship = new Relationship;
        $relationship->forceFill([
            'character_id' => $mine->getKey(),
            'target_character_id' => $target->getKey(),
            'kind' => $kind,
            'label' => filled($label) ? trim($label) : null,
            'status' => RelationshipStatus::Proposed,
            'description' => filled($description) ? trim($description) : null,
            'proposed_by' => $by->getKey(),
        ])->save();

        app(Notifier::class)->send(
            $target->user,
            'relation',
            $mine->fullName().' propose un lien à '.$target->fullName().' ('.$relationship->label().')',
            route('relationships.index', absolute: false),
        );

        return $relationship;
    }

    public function accept(Relationship $relationship, User $by, ?string $description): void
    {
        $this->ensureTargetOwner($relationship, $by);

        $relationship->forceFill([
            'status' => RelationshipStatus::Accepted,
            'target_description' => filled($description) ? trim($description) : null,
            'responded_at' => now(),
        ])->save();

        if ($relationship->character?->user) {
            app(Notifier::class)->send(
                $relationship->character->user,
                'relation',
                $relationship->target?->fullName().' accepte le lien avec '.$relationship->character->fullName(),
                route('characters.show', $relationship->character, absolute: false),
            );
        }
    }

    public function decline(Relationship $relationship, User $by): void
    {
        $this->ensureTargetOwner($relationship, $by);

        $relationship->forceFill(['status' => RelationshipStatus::Declined, 'responded_at' => now()])->save();
    }

    public function withdraw(Relationship $relationship, User $by): void
    {
        if ($relationship->status !== RelationshipStatus::Proposed || $relationship->character?->user_id !== $by->getKey()) {
            throw ValidationException::withMessages(['relationship' => 'Cette proposition ne peut plus être retirée.']);
        }

        $relationship->delete();
    }

    public function end(Relationship $relationship, User $by): void
    {
        if ($relationship->status !== RelationshipStatus::Accepted || ! $this->involves($relationship, $by)) {
            throw ValidationException::withMessages(['relationship' => 'Ce lien ne peut pas être terminé.']);
        }

        $relationship->forceFill(['status' => RelationshipStatus::Ended, 'ended_at' => now()])->save();
    }

    public function describe(Relationship $relationship, User $by, ?string $description): void
    {
        $text = filled($description) ? trim($description) : null;

        if ($relationship->character?->user_id === $by->getKey()) {
            $relationship->forceFill(['description' => $text])->save();
        } elseif ($relationship->target?->user_id === $by->getKey()) {
            $relationship->forceFill(['target_description' => $text])->save();
        } else {
            throw ValidationException::withMessages(['relationship' => 'Ce lien ne concerne pas vos personnages.']);
        }
    }

    public function involves(Relationship $relationship, User $user): bool
    {
        return $relationship->character?->user_id === $user->getKey() || $relationship->target?->user_id === $user->getKey();
    }

    private function ensureTargetOwner(Relationship $relationship, User $by): void
    {
        if ($relationship->status !== RelationshipStatus::Proposed || $relationship->target?->user_id !== $by->getKey()) {
            throw ValidationException::withMessages(['relationship' => 'Cette proposition n’attend pas votre réponse.']);
        }
    }
}
