<?php

namespace Tests\Feature\Concerns;

use App\Enums\CharacterStatus;
use App\Models\Character;
use App\Models\Species;
use App\Models\User;

trait MakesCharacters
{
    /**
     * @return array{0: User, 1: Character}
     */
    protected function playerWithCharacter(string $first, string $last): array
    {
        $user = $this->player();

        return [$user, $this->acceptedCharacter($user, $first, $last)];
    }

    protected function acceptedCharacter(User $user, string $first, string $last): Character
    {
        $character = new Character([
            'first_name' => $first,
            'last_name' => $last,
            'age' => 30,
            'species_id' => Species::query()->value('id'),
            'profession' => 'Libraire',
        ]);
        $character->forceFill([
            'user_id' => $user->id,
            'status' => CharacterStatus::Accepted,
            'accepted_at' => now(),
        ])->save();

        return $character;
    }
}
