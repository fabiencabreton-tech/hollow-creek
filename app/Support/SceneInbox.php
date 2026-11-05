<?php

namespace App\Support;

use App\Enums\ParticipantStatus;
use App\Enums\SceneStatus;
use App\Enums\TurnMode;
use App\Models\Post;
use App\Models\Scene;
use App\Models\SceneDraft;
use App\Models\SceneParticipant;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Ce qui attend un joueur : invitations, scènes où c'est à lui d'écrire,
 * réponses non lues, brouillons à reprendre.
 */
class SceneInbox
{
    /**
     * @return Collection<int, SceneParticipant>
     */
    public static function invitations(User $user): Collection
    {
        return SceneParticipant::query()
            ->with(['scene.creator', 'character'])
            ->where('user_id', $user->getKey())
            ->where('status', ParticipantStatus::Invited->value)
            ->whereHas('scene', fn ($query) => $query->whereIn('status', [SceneStatus::Ongoing->value, SceneStatus::Paused->value]))
            ->latest('updated_at')
            ->get();
    }

    /**
     * Scènes en cours ou en pause du joueur, « à vous » d'abord, puis par activité.
     *
     * @return Collection<int, array{scene: Scene, participant: SceneParticipant, yourTurn: bool, unread: int}>
     */
    public static function active(User $user): Collection
    {
        return SceneParticipant::query()
            ->with(['scene.location', 'character'])
            ->where('user_id', $user->getKey())
            ->where('status', ParticipantStatus::Accepted->value)
            ->whereHas('scene', fn ($query) => $query->whereIn('status', [SceneStatus::Ongoing->value, SceneStatus::Paused->value]))
            ->get()
            ->map(function (SceneParticipant $participant) use ($user): array {
                $scene = $participant->scene;

                return [
                    'scene' => $scene,
                    'participant' => $participant,
                    'yourTurn' => static::isYourTurn($scene, $user),
                    'unread' => Post::query()
                        ->where('scene_id', $scene->getKey())
                        ->when($participant->last_read_post_id, fn ($query, $id) => $query->where('id', '>', $id))
                        ->count(),
                ];
            })
            ->sortBy([
                fn (array $a, array $b): int => $b['yourTurn'] <=> $a['yourTurn'],
                fn (array $a, array $b): int => ($b['scene']->last_post_at?->getTimestamp() ?? 0) <=> ($a['scene']->last_post_at?->getTimestamp() ?? 0),
            ])
            ->values();
    }

    /**
     * @return Collection<int, Scene>
     */
    public static function finished(User $user): Collection
    {
        return Scene::query()
            ->with('location')
            ->whereIn('status', [SceneStatus::Finished->value, SceneStatus::Archived->value])
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->getKey())->whereIn('status', [ParticipantStatus::Accepted->value, ParticipantStatus::Left->value]))
            ->latest('finished_at')
            ->limit(50)
            ->get();
    }

    /**
     * @return Collection<int, SceneDraft>
     */
    public static function drafts(User $user): Collection
    {
        return SceneDraft::query()
            ->with('scene')
            ->where('user_id', $user->getKey())
            ->whereHas('scene')
            ->latest('updated_at')
            ->get();
    }

    public static function isYourTurn(Scene $scene, User $user): bool
    {
        if ($scene->status !== SceneStatus::Ongoing) {
            return false;
        }

        if ($scene->turn_mode === TurnMode::Rounds) {
            return $scene->next_user_id === $user->getKey();
        }

        return $scene->last_post_user_id !== null && $scene->last_post_user_id !== $user->getKey();
    }
}
