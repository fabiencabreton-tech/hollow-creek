<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Models\Character;
use App\Models\Faceclaim;
use App\Models\FaceclaimClaim;
use App\Models\User;
use App\Notifications\ReservationDecided;
use App\Notifications\ReservationExpiringSoon;
use App\Support\ActivityLogger;
use App\Support\Settings;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bottin des avatars : demandes, réservations et attributions.
 *
 * L'anti-doublon repose sur la base : une colonne calculée vaut l'identifiant de la
 * célébrité tant que la demande est active, et un index unique sur cette colonne refuse
 * une seconde demande active, même envoyée à la même seconde. Le code vérifie aussi
 * avant d'écrire, pour donner un message clair.
 */
class FaceclaimRegistry
{
    public const MAX_OPEN_REQUESTS_PER_USER = 3;

    public function __construct(private readonly Settings $settings) {}

    public function findOrCreate(string $name): Faceclaim
    {
        $name = Faceclaim::cleanName($name);
        $key = Faceclaim::key($name);

        if (mb_strlen($name) < 3 || mb_strlen($name) > 120 || strlen($key) < 3) {
            throw ValidationException::withMessages(['name' => 'Indiquez le prénom et le nom de la personnalité (3 à 120 caractères).']);
        }

        $existing = Faceclaim::query()->where('name_key', $key)->first();

        if ($existing) {
            return $existing;
        }

        try {
            return Faceclaim::query()->create(['name' => $name]);
        } catch (UniqueConstraintViolationException) {
            return Faceclaim::query()->where('name_key', $key)->firstOrFail();
        }
    }

    /**
     * Demande de réservation depuis le bottin, examinée ensuite par l'équipe.
     */
    public function requestReservation(User $user, string $name): FaceclaimClaim
    {
        $open = FaceclaimClaim::query()
            ->where('user_id', $user->getKey())
            ->whereNull('character_id')
            ->whereIn('status', [ClaimStatus::Pending->value, ClaimStatus::Reserved->value])
            ->count();

        if ($open >= self::MAX_OPEN_REQUESTS_PER_USER) {
            throw ValidationException::withMessages(['name' => 'Vous avez déjà '.self::MAX_OPEN_REQUESTS_PER_USER.' demandes ou réservations en cours.']);
        }

        $faceclaim = $this->findOrCreate($name);

        return $this->createActiveClaim($faceclaim, [
            'user_id' => $user->getKey(),
            'status' => ClaimStatus::Pending,
        ]);
    }

    /**
     * Demande sans compte : elle ne compte qu'une fois l'adresse e-mail confirmée par lien.
     *
     * @return array{0: FaceclaimClaim, 1: string} la demande et le jeton de confirmation en clair
     */
    public function requestAsGuest(string $name, string $pseudo, string $email): array
    {
        $email = mb_strtolower(trim($email));

        $open = FaceclaimClaim::query()
            ->where('guest_email', $email)
            ->whereNull('user_id')
            ->whereIn('status', [ClaimStatus::Unconfirmed->value, ClaimStatus::Pending->value, ClaimStatus::Reserved->value])
            ->count();

        if ($open >= self::MAX_OPEN_REQUESTS_PER_USER) {
            throw ValidationException::withMessages(['name' => 'Cette adresse a déjà '.self::MAX_OPEN_REQUESTS_PER_USER.' demandes en cours.']);
        }

        $faceclaim = $this->findOrCreate($name);
        $active = FaceclaimClaim::query()->active()->where('faceclaim_id', $faceclaim->getKey())->first();

        if ($active) {
            throw ValidationException::withMessages(['name' => $this->unavailableMessage($faceclaim, $active)]);
        }

        $token = \Illuminate\Support\Str::random(48);

        $claim = new FaceclaimClaim(['faceclaim_id' => $faceclaim->getKey()]);
        $claim->forceFill([
            'guest_email' => $email,
            'guest_pseudo' => trim($pseudo),
            'confirmation_hash' => hash('sha256', $token),
            'status' => ClaimStatus::Unconfirmed,
            'expires_at' => now()->addHours(48),
        ])->save();

        return [$claim->setRelation('faceclaim', $faceclaim), $token];
    }

    /**
     * Lien de confirmation suivi : la demande rejoint celles que l'équipe examine.
     */
    public function confirmGuest(FaceclaimClaim $claim, string $token): void
    {
        if ($claim->status !== ClaimStatus::Unconfirmed
            || $claim->confirmation_hash === null
            || ! hash_equals($claim->confirmation_hash, hash('sha256', $token))
            || ($claim->expires_at && $claim->expires_at->isPast())) {
            throw ValidationException::withMessages(['claim' => 'Ce lien de confirmation n’est plus valable.']);
        }

        try {
            DB::transaction(function () use ($claim): void {
                $active = FaceclaimClaim::query()->active()->where('faceclaim_id', $claim->faceclaim_id)->lockForUpdate()->first();

                if ($active) {
                    throw ValidationException::withMessages(['claim' => $this->unavailableMessage($claim->faceclaim, $active)]);
                }

                $claim->forceFill([
                    'status' => ClaimStatus::Pending,
                    'confirmed_at' => now(),
                    'confirmation_hash' => null,
                    'expires_at' => null,
                ])->save();
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['claim' => 'Une autre demande pour '.$claim->faceclaim->name.' vient d’être enregistrée.']);
        }
    }

    /**
     * Le compte créé avec la même adresse (vérifiée) récupère les demandes faites sans compte.
     */
    public function attachGuestClaims(User $user): int
    {
        return FaceclaimClaim::query()
            ->whereNull('user_id')
            ->where('guest_email', mb_strtolower($user->email))
            ->whereIn('status', [ClaimStatus::Pending->value, ClaimStatus::Reserved->value])
            ->update(['user_id' => $user->getKey()]);
    }

    public function accept(FaceclaimClaim $claim, User $staff): void
    {
        $this->ensureStatus($claim, [ClaimStatus::Pending]);

        $claim->forceFill([
            'status' => ClaimStatus::Reserved,
            'expires_at' => now()->addDays($this->settings->reservationDays()),
            'decided_by' => $staff->getKey(),
            'decided_at' => now(),
        ])->save();

        ActivityLogger::record('bottin.reservation-acceptee', $claim, $claim->faceclaim->name, causer: $staff);
        $this->notifyClaimant($claim, 'Réservation acceptée : '.$claim->faceclaim->name, new ReservationDecided($claim));
    }

    public function refuse(FaceclaimClaim $claim, User $staff, ?string $note = null): void
    {
        $this->ensureStatus($claim, [ClaimStatus::Pending]);

        $claim->forceFill([
            'status' => ClaimStatus::Refused,
            'decided_by' => $staff->getKey(),
            'decided_at' => now(),
            'note' => $note,
        ])->save();

        ActivityLogger::record('bottin.reservation-refusee', $claim, $claim->faceclaim->name, causer: $staff);
        $this->notifyClaimant($claim, 'Réservation refusée : '.$claim->faceclaim->name, new ReservationDecided($claim));
    }

    /**
     * Libère une célébrité (réservation abandonnée, personnage refusé ou archivé…).
     */
    public function release(FaceclaimClaim $claim, ?User $by = null, ?string $note = null): void
    {
        if (! $claim->status->isActive()) {
            return;
        }

        $claim->forceFill([
            'status' => ClaimStatus::Released,
            'decided_by' => $by?->getKey() ?? $claim->decided_by,
            'decided_at' => now(),
            'note' => $note ?? $claim->note,
        ])->save();

        ActivityLogger::record('bottin.liberation', $claim, $claim->faceclaim->name, causer: $by);
    }

    /**
     * Une réservation acceptée peut être prolongée une seule fois.
     */
    public function extend(FaceclaimClaim $claim): void
    {
        if (! $claim->canBeExtended()) {
            throw ValidationException::withMessages(['claim' => 'Cette réservation ne peut plus être prolongée.']);
        }

        $claim->forceFill([
            'expires_at' => $claim->expires_at->copy()->addDays($this->settings->reservationDays()),
            'extended_at' => now(),
            'reminded_at' => null,
        ])->save();
    }

    /**
     * Choix de l'avatar d'une fiche.
     * - une célébrité réservée par le joueur lui est rattachée ;
     * - une célébrité libre est mise de côté le temps de terminer la fiche ;
     * - une célébrité demandée, réservée ou prise par quelqu'un d'autre est refusée.
     */
    public function attachToCharacter(Character $character, string $name): FaceclaimClaim
    {
        $faceclaim = $this->findOrCreate($name);

        return DB::transaction(function () use ($character, $faceclaim): FaceclaimClaim {
            $current = $character->activeClaim()->first();

            if ($current && $current->faceclaim_id === $faceclaim->getKey()) {
                return $current;
            }

            $active = FaceclaimClaim::query()->active()->where('faceclaim_id', $faceclaim->getKey())->lockForUpdate()->first();

            if ($active) {
                $isOwnReservation = $active->user_id === $character->user_id && $active->character_id === null;

                if (! $isOwnReservation) {
                    throw ValidationException::withMessages(['faceclaim' => $this->unavailableMessage($faceclaim, $active)]);
                }

                $active->forceFill(['character_id' => $character->getKey()])->save();
                $claim = $active;
            } else {
                $claim = $this->createActiveClaim($faceclaim, [
                    'user_id' => $character->user_id,
                    'character_id' => $character->getKey(),
                    'status' => ClaimStatus::Pending,
                    'expires_at' => now()->addDays($this->settings->reservationDays()),
                ]);
            }

            if ($current) {
                $this->detachPrevious($current);
            }

            $character->forceFill(['faceclaim_id' => $faceclaim->getKey()])->save();

            return $claim;
        });
    }

    /**
     * Réservations arrivées à échéance : la célébrité redevient disponible.
     */
    public function expireOverdue(): int
    {
        $expired = 0;

        FaceclaimClaim::query()
            ->whereIn('status', [ClaimStatus::Unconfirmed->value, ClaimStatus::Pending->value, ClaimStatus::Reserved->value])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->each(function (FaceclaimClaim $claim) use (&$expired): void {
                $claim->forceFill(['status' => ClaimStatus::Expired])->save();
                $expired++;
            });

        return $expired;
    }

    /**
     * Rappel envoyé 48 heures avant la fin d'une réservation.
     */
    public function remindExpiringSoon(): int
    {
        $sent = 0;

        FaceclaimClaim::query()
            ->with(['user', 'faceclaim'])
            ->where('status', ClaimStatus::Reserved->value)
            ->whereNull('reminded_at')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addHours(48)])
            ->each(function (FaceclaimClaim $claim) use (&$sent): void {
                $this->notifyClaimant($claim, 'Votre réservation de '.$claim->faceclaim->name.' arrive à échéance', new ReservationExpiringSoon($claim));
                $claim->forceFill(['reminded_at' => now()])->save();
                $sent++;
            });

        return $sent;
    }

    private function notifyClaimant(FaceclaimClaim $claim, string $title, \Illuminate\Notifications\Notification $mail): void
    {
        if ($claim->user) {
            app(Notifier::class)->send($claim->user, 'bottin', $title, route('faceclaims.index', absolute: false), $claim->note, $mail);
        } elseif ($claim->guest_email && $claim->confirmed_at) {
            \Illuminate\Support\Facades\Notification::route('mail', $claim->guest_email)->notify($mail);
        }
    }

    public function unavailableMessage(Faceclaim $faceclaim, FaceclaimClaim $active): string
    {
        return match ($active->status) {
            ClaimStatus::Taken => "{$faceclaim->name} représente déjà un personnage.",
            ClaimStatus::Reserved => "{$faceclaim->name} est réservé(e) par un autre joueur.",
            default => "Une demande est déjà en cours pour {$faceclaim->name}.",
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createActiveClaim(Faceclaim $faceclaim, array $attributes): FaceclaimClaim
    {
        try {
            return DB::transaction(function () use ($faceclaim, $attributes): FaceclaimClaim {
                $active = FaceclaimClaim::query()->active()->where('faceclaim_id', $faceclaim->getKey())->lockForUpdate()->first();

                if ($active) {
                    throw ValidationException::withMessages(['name' => $this->unavailableMessage($faceclaim, $active)]);
                }

                $claim = new FaceclaimClaim(['faceclaim_id' => $faceclaim->getKey()]);
                $claim->forceFill($attributes)->save();

                return $claim->setRelation('faceclaim', $faceclaim);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => "Une autre demande pour {$faceclaim->name} vient d’être enregistrée."]);
        }
    }

    /**
     * L'ancien avatar d'une fiche : une mise de côté est libérée, une réservation
     * du joueur lui reste acquise (seulement détachée de la fiche).
     */
    private function detachPrevious(FaceclaimClaim $previous): void
    {
        if ($previous->status === ClaimStatus::Reserved) {
            $previous->forceFill(['character_id' => null])->save();

            return;
        }

        $this->release($previous, note: 'Avatar changé sur la fiche');
    }

    /**
     * @param  array<int, ClaimStatus>  $allowed
     */
    private function ensureStatus(FaceclaimClaim $claim, array $allowed): void
    {
        if (! in_array($claim->status, $allowed, true)) {
            throw ValidationException::withMessages(['claim' => 'Cette demande a déjà été traitée.']);
        }

        if ($claim->character_id !== null) {
            // Une célébrité choisie dans une fiche se décide avec la fiche elle-même.
            throw ValidationException::withMessages(['claim' => 'Cette demande se décide avec la fiche du personnage.']);
        }
    }
}
