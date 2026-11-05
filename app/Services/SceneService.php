<?php

namespace App\Services;

use App\Enums\CharacterStatus;
use App\Enums\ParticipantStatus;
use App\Enums\SceneStatus;
use App\Enums\TurnMode;
use App\Models\Character;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Scene;
use App\Models\SceneDraft;
use App\Models\SceneParticipant;
use App\Models\User;
use App\Notifications\SceneInvitation;
use App\Notifications\ScenePostPublished;
use App\Support\ActivityLogger;
use App\Support\SceneText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Vie d'une scène : ouverture, invitations, ordre de passage, réponses, statuts.
 * Les contrôles d'accès sont faits par App\Policies\ScenePolicy avant l'appel ;
 * ce service vérifie en plus la cohérence des données (personnage accepté, à soi…).
 */
class SceneService
{
    /**
     * @param  array<string, mixed>  $data  title, rp_date, location_id, visibility, is_open, content_warnings, turn_mode
     * @param  array<int, int>  $invitedCharacterIds
     */
    public function create(User $user, Character $character, array $data, array $invitedCharacterIds, string $introduction): Scene
    {
        $this->ensurePlayable($character, $user);

        $scene = DB::transaction(function () use ($user, $character, $data, $invitedCharacterIds, $introduction): Scene {
            $scene = new Scene($data);
            $scene->forceFill([
                'user_id' => $user->getKey(),
                'status' => SceneStatus::Ongoing,
            ])->save();

            $this->participant($scene, $user)->forceFill([
                'character_id' => $character->getKey(),
                'status' => ParticipantStatus::Accepted,
                'position' => 1,
                'joined_at' => now(),
            ])->save();

            foreach ($this->invitableCharacters($invitedCharacterIds, $user) as $invited) {
                $this->createInvitation($scene, $invited, $user);
            }

            $this->publish($scene, $user, $character, $introduction, notify: false);

            return $scene;
        });

        ActivityLogger::record('scene.ouverte', $scene, $scene->title, causer: $user);

        $scene->participants()->with('user')->where('status', ParticipantStatus::Invited->value)->get()
            ->each(fn (SceneParticipant $participant) => $participant->user?->notify(new SceneInvitation($scene, $user)));

        return $scene->refresh();
    }

    public function invite(Scene $scene, User $by, Character $character): SceneParticipant
    {
        $invited = $this->invitableCharacters([$character->getKey()], $by)->first();

        if ($invited === null) {
            throw ValidationException::withMessages(['character_id' => 'Ce personnage ne peut pas être invité.']);
        }

        $existing = $scene->participantFor($invited->user);

        if ($existing && in_array($existing->status, [ParticipantStatus::Invited, ParticipantStatus::Accepted], true)) {
            throw ValidationException::withMessages(['character_id' => 'Ce joueur participe déjà à la scène ou y est déjà invité.']);
        }

        $participant = DB::transaction(fn (): SceneParticipant => $this->createInvitation($scene, $invited, $by));

        $invited->user?->notify(new SceneInvitation($scene, $by));

        return $participant;
    }

    public function acceptInvitation(SceneParticipant $participant, Character $character): void
    {
        if ($participant->status !== ParticipantStatus::Invited) {
            throw ValidationException::withMessages(['invitation' => 'Cette invitation n’est plus valable.']);
        }

        $this->ensurePlayable($character, $participant->user);

        DB::transaction(function () use ($participant, $character): void {
            $participant->forceFill([
                'character_id' => $character->getKey(),
                'status' => ParticipantStatus::Accepted,
                'joined_at' => now(),
            ])->save();

            $this->refreshTurn($participant->scene);
        });
    }

    public function declineInvitation(SceneParticipant $participant): void
    {
        if ($participant->status === ParticipantStatus::Invited) {
            $participant->forceFill(['status' => ParticipantStatus::Declined])->save();
        }
    }

    public function cancelInvitation(SceneParticipant $participant): void
    {
        if ($participant->status !== ParticipantStatus::Invited) {
            throw ValidationException::withMessages(['invitation' => 'Seule une invitation en attente peut être retirée.']);
        }

        $participant->delete();
    }

    /**
     * Rejoindre une scène publique laissée ouverte.
     */
    public function join(Scene $scene, User $user, Character $character): void
    {
        $this->ensurePlayable($character, $user);

        DB::transaction(function () use ($scene, $user, $character): void {
            $participant = $this->participant($scene, $user);

            $participant->forceFill([
                'character_id' => $character->getKey(),
                'status' => ParticipantStatus::Accepted,
                'position' => $this->nextPosition($scene),
                'joined_at' => now(),
            ])->save();

            $this->refreshTurn($scene);
        });
    }

    public function leave(Scene $scene, User $user): void
    {
        DB::transaction(function () use ($scene, $user): void {
            $participant = $scene->participantFor($user);

            if ($participant === null || $participant->status !== ParticipantStatus::Accepted) {
                return;
            }

            $wasNext = $scene->next_user_id === $user->getKey();

            $participant->forceFill(['status' => ParticipantStatus::Left])->save();

            if ($wasNext) {
                $scene->forceFill(['next_user_id' => $this->userAfter($scene, $user->getKey())])->save();
            }

            SceneDraft::query()->where('scene_id', $scene->getKey())->where('user_id', $user->getKey())->delete();
        });
    }

    /**
     * Nouvelle réponse : compteurs, ordre de passage, brouillon effacé, partenaires prévenus.
     */
    public function reply(Scene $scene, User $user, string $body): Post
    {
        $participant = $scene->participantFor($user);

        if ($participant === null || $participant->status !== ParticipantStatus::Accepted || $participant->character === null) {
            throw ValidationException::withMessages(['body' => 'Vous ne participez pas à cette scène.']);
        }

        return DB::transaction(fn (): Post => $this->publish($scene, $user, $participant->character, $body, notify: true));
    }

    public function editPost(Post $post, User $user, string $body): void
    {
        if (trim($body) === trim($post->body)) {
            return;
        }

        DB::transaction(function () use ($post, $user, $body): void {
            $revision = new PostRevision;
            $revision->forceFill([
                'post_id' => $post->getKey(),
                'user_id' => $user->getKey(),
                'body' => $post->body,
            ])->save();

            $words = SceneText::wordCount($body);
            $difference = $words - $post->word_count;

            $post->forceFill([
                'body' => $body,
                'word_count' => $words,
                'edited_at' => now(),
            ])->save();

            Scene::query()->whereKey($post->scene_id)->update([
                'words_count' => DB::raw('CASE WHEN words_count + ('.(int) $difference.') < 0 THEN 0 ELSE words_count + ('.(int) $difference.') END'),
            ]);
        });
    }

    public function changeStatus(Scene $scene, SceneStatus $status, User $by): void
    {
        if ($scene->status === $status) {
            return;
        }

        $scene->forceFill([
            'status' => $status,
            'finished_at' => $status === SceneStatus::Finished ? now() : ($status === SceneStatus::Archived ? $scene->finished_at : null),
        ])->save();

        ActivityLogger::record('scene.statut', $scene, $scene->title.' : '.$status->getLabel(), causer: $by);
    }

    public function saveDraft(Scene $scene, User $user, string $body): ?SceneDraft
    {
        $query = SceneDraft::query()->where('scene_id', $scene->getKey())->where('user_id', $user->getKey());

        if (trim($body) === '') {
            $query->delete();

            return null;
        }

        // Sauvegarde automatique arrivée après la publication : le texte est déjà dans la scène.
        $lastOwnPost = Post::query()->where('scene_id', $scene->getKey())->where('user_id', $user->getKey())->latest('id')->value('body');

        if ($lastOwnPost !== null && trim($lastOwnPost) === trim($body)) {
            $query->delete();

            return null;
        }

        $draft = $query->first() ?? new SceneDraft;
        $draft->forceFill([
            'scene_id' => $scene->getKey(),
            'user_id' => $user->getKey(),
            'body' => $body,
        ]);
        // Toujours rafraîchir la date, même si le texte n'a pas changé.
        $draft->updated_at = now();
        $draft->save();

        return $draft;
    }

    public function markRead(SceneParticipant $participant, int $postId): void
    {
        if ($participant->last_read_post_id === null || $participant->last_read_post_id < $postId) {
            $participant->forceFill(['last_read_post_id' => $postId])->save();
        }
    }

    /**
     * Personnages acceptés d'un joueur, prêts à jouer.
     *
     * @return Collection<int, Character>
     */
    public function playableCharacters(User $user): Collection
    {
        return Character::query()->where('user_id', $user->getKey())->accepted()->orderBy('first_name')->get();
    }

    private function publish(Scene $scene, User $user, Character $character, string $body, bool $notify): Post
    {
        $words = SceneText::wordCount($body);

        $post = new Post;
        $post->forceFill([
            'scene_id' => $scene->getKey(),
            'user_id' => $user->getKey(),
            'character_id' => $character->getKey(),
            'character_name' => $character->fullName(),
            'body' => $body,
            'word_count' => $words,
        ])->save();

        $scene->forceFill([
            'posts_count' => $scene->posts_count + 1,
            'words_count' => $scene->words_count + $words,
            'last_post_at' => now(),
            'last_post_user_id' => $user->getKey(),
            'next_user_id' => $scene->turn_mode === TurnMode::Rounds ? $this->userAfter($scene, $user->getKey()) : null,
        ])->save();

        SceneDraft::query()->where('scene_id', $scene->getKey())->where('user_id', $user->getKey())->delete();

        $participant = $scene->participantFor($user);

        if ($participant) {
            $this->markRead($participant, $post->getKey());
        }

        if ($notify) {
            DB::afterCommit(fn () => $this->notifyPartners($scene, $post, $user));
        }

        return $post;
    }

    private function notifyPartners(Scene $scene, Post $post, User $author): void
    {
        $scene->activeParticipants()->with('user')->get()
            ->reject(fn (SceneParticipant $participant): bool => $participant->user_id === $author->getKey())
            ->each(function (SceneParticipant $participant) use ($scene, $post): void {
                $user = $participant->user;

                if ($user === null || $user->isSuspended()) {
                    return;
                }

                $isTheirTurn = $scene->turn_mode === TurnMode::Rounds && $scene->next_user_id === $user->getKey();

                $wanted = match ($user->scene_mail) {
                    'aucune' => false,
                    'tour' => $isTheirTurn,
                    default => true,
                };

                if ($wanted) {
                    $user->notify(new ScenePostPublished($scene, $post, $isTheirTurn));
                }
            });
    }

    /**
     * Ordre de passage : le participant actif suivant, dans l'ordre d'arrivée.
     */
    private function userAfter(Scene $scene, ?int $userId): ?int
    {
        if ($scene->turn_mode !== TurnMode::Rounds) {
            return null;
        }

        $order = $scene->participants()
            ->where('status', ParticipantStatus::Accepted->value)
            ->pluck('user_id')
            ->values();

        if ($order->isEmpty()) {
            return null;
        }

        $index = $order->search($userId);

        if ($index === false) {
            return $order->first();
        }

        return $order->get(($index + 1) % $order->count());
    }

    /**
     * Après une arrivée : si personne n'est attendu, le tour passe au suivant du dernier auteur.
     */
    private function refreshTurn(Scene $scene): void
    {
        if ($scene->turn_mode !== TurnMode::Rounds) {
            return;
        }

        $current = $scene->next_user_id;
        $stillActive = $current !== null && $scene->participants()->where('user_id', $current)->where('status', ParticipantStatus::Accepted->value)->exists();
        $soloAuthor = $current !== null && $current === $scene->last_post_user_id;

        if (! $stillActive || $soloAuthor) {
            $scene->forceFill(['next_user_id' => $this->userAfter($scene, $scene->last_post_user_id)])->save();
        }
    }

    private function nextPosition(Scene $scene): int
    {
        return (int) $scene->participants()->max('position') + 1;
    }

    private function participant(Scene $scene, User $user): SceneParticipant
    {
        $participant = SceneParticipant::query()
            ->where('scene_id', $scene->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if ($participant === null) {
            $participant = new SceneParticipant;
            $participant->forceFill(['scene_id' => $scene->getKey(), 'user_id' => $user->getKey()]);
        }

        return $participant;
    }

    private function createInvitation(Scene $scene, Character $character, User $by): SceneParticipant
    {
        $participant = $this->participant($scene, $character->user);

        $participant->forceFill([
            'character_id' => $character->getKey(),
            'status' => ParticipantStatus::Invited,
            'invited_by' => $by->getKey(),
            'position' => $this->nextPosition($scene),
            'joined_at' => null,
        ])->save();

        return $participant;
    }

    /**
     * Personnages acceptés d'autres joueurs, un par joueur.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, Character>
     */
    private function invitableCharacters(array $ids, User $by): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return Character::query()
            ->with('user')
            ->whereIn('id', array_slice(array_map('intval', $ids), 0, 10))
            ->where('status', CharacterStatus::Accepted->value)
            ->where('user_id', '!=', $by->getKey())
            ->whereHas('user', fn ($query) => $query->whereNull('suspended_at'))
            ->get()
            ->unique('user_id')
            ->values();
    }

    private function ensurePlayable(Character $character, ?User $user): void
    {
        if ($user === null || $character->user_id !== $user->getKey() || $character->status !== CharacterStatus::Accepted) {
            throw ValidationException::withMessages(['character_id' => 'Choisissez l’un de vos personnages acceptés.']);
        }
    }
}
