<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Clue;
use Illuminate\Support\Collection;

/**
 * Remise des indices : par l'équipe depuis l'administration, ou d'un personnage
 * à un autre quand l'indice est transmissible. Chaque joueur concerné est prévenu.
 */
class ClueService
{
    public function __construct(private readonly Notifier $notifier) {}

    /**
     * Prévient les joueurs des personnages qui viennent de recevoir l'indice.
     *
     * @param  iterable<Character>  $characters
     */
    public function notifyHolders(Clue $clue, iterable $characters, ?Character $sharedBy = null): void
    {
        $clue->loadMissing('investigation');

        collect($characters)
            ->filter(fn (Character $character) => $character->user !== null)
            ->groupBy('user_id')
            ->each(function (Collection $characters) use ($clue, $sharedBy): void {
                $names = $characters->map(fn (Character $character) => $character->fullName())->join(', ', ' et ');
                $from = $sharedBy ? ' de la part de '.$sharedBy->fullName() : '';

                $this->notifier->send(
                    $characters->first()->user,
                    'indice',
                    'Nouvel indice pour '.$names.$from.' : '.$clue->title,
                    route('clues.show', [$clue->investigation, $clue], absolute: false),
                    $clue->investigation?->title,
                );
            });
    }

    /**
     * Un personnage transmet l'indice à un autre. Rien ne se passe si le destinataire l'a déjà.
     */
    public function share(Clue $clue, Character $from, Character $to): bool
    {
        if ($clue->characters()->whereKey($to->getKey())->exists()) {
            return false;
        }

        $clue->characters()->attach($to->getKey(), ['shared_by_character_id' => $from->getKey()]);
        $this->notifyHolders($clue, [$to->loadMissing('user')], $from);

        return true;
    }
}
