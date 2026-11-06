<?php

namespace App\Services;

use App\Enums\CharacterStatus;
use App\Enums\ClaimStatus;
use App\Enums\FamilyRequestStatus;
use App\Models\Character;
use App\Models\FaceclaimClaim;
use App\Models\FamilyRequest;
use App\Models\User;
use App\Models\ValidationItem;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * File de validation unique de l'équipe : ce qui attend une décision, quel que soit
 * son type, et les décisions elles-mêmes (accepter, demander une modification,
 * refuser), qui passent par les services habituels et préviennent les membres.
 */
class ValidationDesk
{
    public const ACCEPT = 'accepter';

    public const CHANGES = 'modifier';

    public const REFUSE = 'refuser';

    /**
     * Actions du journal qui forment l'historique des décisions.
     */
    public const HISTORY_ACTIONS = [
        'fiche.acceptee' => 'Fiche acceptée',
        'fiche.modifications' => 'Modifications demandées',
        'fiche.refusee' => 'Fiche refusée',
        'bottin.reservation-acceptee' => 'Avatar accepté',
        'bottin.reservation-refusee' => 'Avatar refusé',
        'famille.demande-acceptee' => 'Demande de famille acceptée',
        'famille.demande-refusee' => 'Demande de famille refusée',
        'compte.email-valide' => 'Inscription validée',
        'inscription.refusee' => 'Inscription refusée',
    ];

    /**
     * @return Builder<ValidationItem>
     */
    public function query(): Builder
    {
        $characters = DB::table('characters')
            ->selectRaw("id * 10 + 1 as uid, 'fiche' as kind, id as item_id, user_id, submitted_at as submitted_at")
            ->where('status', CharacterStatus::Pending->value)
            ->whereNull('deleted_at');

        $claims = DB::table('faceclaim_claims')
            ->selectRaw("id * 10 + 2 as uid, 'avatar' as kind, id as item_id, user_id, created_at as submitted_at")
            ->where('status', ClaimStatus::Pending->value)
            ->whereNull('character_id');

        $families = DB::table('family_requests')
            ->selectRaw("id * 10 + 3 as uid, 'famille' as kind, id as item_id, user_id, created_at as submitted_at")
            ->where('status', FamilyRequestStatus::Pending->value);

        $accounts = DB::table('users')
            ->selectRaw("id * 10 + 4 as uid, 'inscription' as kind, id as item_id, id as user_id, created_at as submitted_at")
            ->whereNull('email_verified_at')
            ->whereNull('suspended_at');

        $union = $characters->unionAll($claims)->unionAll($families)->unionAll($accounts);

        return ValidationItem::query()->fromSub($union, 'validation_items');
    }

    public function count(): int
    {
        return $this->query()->count();
    }

    /**
     * L'objet réel derrière une ligne de la file.
     */
    public function subject(ValidationItem $item): ?Model
    {
        return match ($item->kind) {
            'fiche' => Character::query()->with(['user', 'species', 'traits', 'family', 'faceclaim', 'kirisawaVictim', 'reviews.reviewer'])->find($item->item_id),
            'avatar' => FaceclaimClaim::query()->with(['faceclaim', 'user'])->find($item->item_id),
            'famille' => FamilyRequest::query()->with(['character', 'family', 'user'])->find($item->item_id),
            'inscription' => User::query()->find($item->item_id),
            default => null,
        };
    }

    public function title(ValidationItem $item): string
    {
        $subject = $this->subject($item);

        return match (true) {
            $subject instanceof Character => $subject->fullName(),
            $subject instanceof FaceclaimClaim => $subject->faceclaim?->name ?? 'Avatar',
            $subject instanceof FamilyRequest => $subject->label().' · '.$subject->character?->fullName(),
            $subject instanceof User => $subject->pseudo.' · '.$subject->email,
            default => 'Élément introuvable',
        };
    }

    /**
     * Décisions possibles pour ce type.
     *
     * @return array<string, string>
     */
    public function decisions(ValidationItem $item): array
    {
        return match ($item->kind) {
            'fiche' => [self::ACCEPT => 'Accepter', self::CHANGES => 'Demander une modification', self::REFUSE => 'Refuser'],
            'inscription' => [self::ACCEPT => 'Valider l’adresse et l’inscription', self::REFUSE => 'Refuser et supprimer le compte'],
            default => [self::ACCEPT => 'Accepter', self::REFUSE => 'Refuser'],
        };
    }

    public function canDecide(User $staff, ValidationItem $item): bool
    {
        return match ($item->kind) {
            'fiche', 'famille' => $staff->can('personnages.valider'),
            'avatar' => $staff->can('bottin.gerer') || $staff->can('personnages.valider'),
            'inscription' => $staff->can('comptes.moderer'),
            default => false,
        };
    }

    public function decide(User $staff, ValidationItem $item, string $decision, ?string $reason): void
    {
        if (! $this->canDecide($staff, $item)) {
            abort(403);
        }

        if (! array_key_exists($decision, $this->decisions($item))) {
            throw ValidationException::withMessages(['decision' => 'Cette décision n’est pas possible ici.']);
        }

        $reason = filled($reason) ? trim($reason) : null;
        if ($decision !== self::ACCEPT && $reason === null) {
            throw ValidationException::withMessages(['reason' => 'Expliquez votre décision : le membre recevra ce message.']);
        }

        $subject = $this->subject($item);
        if ($subject === null) {
            throw ValidationException::withMessages(['decision' => 'Cet élément n’existe plus.']);
        }

        match (true) {
            $subject instanceof Character => match ($decision) {
                self::ACCEPT => app(CharacterWorkflow::class)->accept($subject, $staff, $reason),
                self::CHANGES => app(CharacterWorkflow::class)->requestChanges($subject, $staff, $reason),
                default => app(CharacterWorkflow::class)->refuse($subject, $staff, $reason),
            },
            $subject instanceof FaceclaimClaim => $decision === self::ACCEPT
                ? app(FaceclaimRegistry::class)->accept($subject, $staff)
                : app(FaceclaimRegistry::class)->refuse($subject, $staff, $reason),
            $subject instanceof FamilyRequest => $decision === self::ACCEPT
                ? app(FamilyService::class)->approve($subject, $staff, $reason)
                : app(FamilyService::class)->refuse($subject, $staff, $reason),
            $subject instanceof User => $decision === self::ACCEPT
                ? $this->validateAccount($subject, $staff)
                : $this->refuseAccount($subject, $staff, $reason),
        };
    }

    private function validateAccount(User $user, User $staff): void
    {
        $user->forceFill(['email_verified_at' => now()])->save();
        ActivityLogger::record('compte.email-valide', $user, $user->pseudo, causer: $staff);
    }

    /**
     * Une inscription refusée (adresse jamais confirmée) est supprimée ; le motif reste au journal.
     */
    private function refuseAccount(User $user, User $staff, string $reason): void
    {
        if ($user->email_verified_at !== null) {
            throw ValidationException::withMessages(['decision' => 'Ce compte est déjà actif : utilisez la suspension depuis « Comptes ».']);
        }

        ActivityLogger::record('inscription.refusee', null, $user->pseudo, ['motif' => $reason], causer: $staff);
        app(AccountData::class)->delete($user, true);
    }
}
