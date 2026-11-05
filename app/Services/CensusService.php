<?php

namespace App\Services;

use App\Enums\CharacterStatus;
use App\Models\Census;
use App\Models\CensusResponse;
use App\Models\Character;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;

/**
 * Recensement d'activité : annonce, réponses des joueurs, archivage des personnages non recensés.
 */
class CensusService
{
    /**
     * Prévient chaque joueur ayant au moins un personnage accepté.
     */
    public function announce(Census $census): int
    {
        $sent = 0;

        User::query()
            ->whereNull('suspended_at')
            ->whereHas('characters', fn ($query) => $query->where('status', CharacterStatus::Accepted->value))
            ->each(function (User $user) use ($census, &$sent): void {
                app(Notifier::class)->send(
                    $user,
                    'recensement',
                    'Recensement : confirmez vos personnages avant le '.$census->ends_at->locale('fr')->isoFormat('LL'),
                    route('census.show', absolute: false),
                    $census->message,
                );
                $sent++;
            });

        return $sent;
    }

    /**
     * @param  array<int, int>  $characterIds
     */
    public function confirm(Census $census, User $user, array $characterIds): int
    {
        $characters = Character::query()
            ->where('user_id', $user->getKey())
            ->where('status', CharacterStatus::Accepted->value)
            ->whereIn('id', $characterIds)
            ->get();

        foreach ($characters as $character) {
            $exists = CensusResponse::query()->where('census_id', $census->getKey())->where('character_id', $character->getKey())->exists();

            if (! $exists) {
                $response = new CensusResponse;
                $response->forceFill([
                    'census_id' => $census->getKey(),
                    'character_id' => $character->getKey(),
                    'user_id' => $user->getKey(),
                    'created_at' => now(),
                ])->save();
            }
        }

        return $characters->count();
    }

    /**
     * Archive les personnages non recensés, sauf ceux dont l'absence est annoncée.
     */
    public function archiveMissing(Census $census, User $staff): int
    {
        $archived = 0;

        DB::transaction(function () use ($census, $staff, &$archived): void {
            foreach ($census->missingCharacters() as $character) {
                if ($character->isAbsent()) {
                    continue;
                }

                app(CharacterWorkflow::class)->archive($character, $staff, 'Non recensé : '.$census->title);
                $archived++;
            }

            $census->forceFill(['closed_at' => now()])->save();
        });

        ActivityLogger::record('recensement.archivage', $census, $census->title.' : '.$archived.' personnage(s) archivé(s)', causer: $staff);

        return $archived;
    }
}
