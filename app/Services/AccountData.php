<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Models\Character;
use App\Models\ClassifiedAd;
use App\Models\ContactTicket;
use App\Models\EventParticipant;
use App\Models\FaceclaimClaim;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\JournalEntry;
use App\Models\Message;
use App\Models\Post;
use App\Models\Relationship;
use App\Models\Scene;
use App\Models\SceneDraft;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;

/**
 * « Mes données » : export de tout ce que le joueur a écrit, et suppression du compte.
 * Par défaut, les réponses restent dans les scènes sous un auteur anonyme pour ne pas
 * trouer l'histoire des partenaires ; le joueur peut choisir de les effacer.
 */
class AccountData
{
    public function __construct(
        private readonly CharacterWorkflow $workflow,
        private readonly FaceclaimRegistry $registry,
    ) {}

    /**
     * Données structurées (format réutilisable).
     *
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        $characters = Character::withTrashed()->where('user_id', $user->getKey())->with(['species', 'family', 'faceclaim'])->get();

        return [
            'exporte_le' => now()->toIso8601String(),
            'compte' => [
                'pseudo' => $user->pseudo,
                'email' => $user->email,
                'inscription' => $user->created_at?->toIso8601String(),
                'email_verifie_le' => $user->email_verified_at?->toIso8601String(),
                'preferences_notifications' => $user->notification_preferences,
                'notifications_sur_appareils' => $user->push_preferences,
                'appareils_abonnes' => $user->pushSubscriptions()->get()->map(fn ($device): array => [
                    'appareil' => $device->device,
                    'depuis' => $device->created_at?->toIso8601String(),
                    'derniere_alerte' => $device->last_used_at?->toIso8601String(),
                ])->all(),
            ],
            'personnages' => $characters->map(fn (Character $character): array => [
                'nom' => $character->fullName(),
                'statut' => $character->status->getLabel(),
                'age' => $character->age,
                'age_apparent' => $character->apparent_age,
                'espece' => $character->species?->name,
                'famille' => $character->family?->name,
                'profession' => $character->profession,
                'citation' => $character->quote,
                'celebrite' => $character->faceclaim?->name,
                'credit_avatar' => $character->avatar_credit,
                'histoire' => $character->history,
                'caractere' => $character->personality,
                'absence' => $character->absence_note,
            ])->values()->all(),
            'reponses' => Post::withTrashed()->where('user_id', $user->getKey())->with('scene')->orderBy('id')->get()
                ->map(fn (Post $post): array => [
                    'scene' => $post->scene?->title,
                    'personnage' => $post->character_name,
                    'texte' => $post->body,
                    'publiee_le' => $post->created_at?->toIso8601String(),
                    'modifiee_le' => $post->edited_at?->toIso8601String(),
                ])->all(),
            'brouillons' => SceneDraft::query()->where('user_id', $user->getKey())->with('scene')->get()
                ->map(fn (SceneDraft $draft): array => ['scene' => $draft->scene?->title, 'texte' => $draft->body])->all(),
            'murmures' => FeedPost::withTrashed()->where('user_id', $user->getKey())->orderBy('id')->get()
                ->map(fn (FeedPost $post): array => [
                    'personnage' => $post->character_name,
                    'type' => $post->kind->getLabel(),
                    'texte' => $post->body,
                    'credit_image' => $post->image_credit,
                    'publie_le' => $post->created_at?->toIso8601String(),
                ])->all(),
            'commentaires' => FeedComment::withTrashed()->where('user_id', $user->getKey())->orderBy('id')->get()
                ->map(fn (FeedComment $comment): array => [
                    'personnage' => $comment->character_name,
                    'texte' => $comment->body,
                    'publie_le' => $comment->created_at?->toIso8601String(),
                ])->all(),
            'journal' => JournalEntry::withTrashed()->where('user_id', $user->getKey())->with('character')->orderBy('id')->get()
                ->map(fn (JournalEntry $entry): array => [
                    'personnage' => $entry->character?->fullName(),
                    'titre' => $entry->title,
                    'date_rp' => $entry->rp_date,
                    'texte' => $entry->body,
                    'privee' => $entry->is_private,
                    'ecrite_le' => $entry->created_at?->toIso8601String(),
                ])->all(),
            'annonces' => ClassifiedAd::withTrashed()->where('user_id', $user->getKey())->orderBy('id')->get()
                ->map(fn (ClassifiedAd $ad): array => [
                    'personnage' => $ad->character_name,
                    'rubrique' => $ad->category->getLabel(),
                    'titre' => $ad->title,
                    'texte' => $ad->body,
                    'pour_repondre' => $ad->contact,
                    'publiee_le' => $ad->created_at?->toIso8601String(),
                ])->all(),
            'messages_envoyes' => Message::query()->where('user_id', $user->getKey())->orderBy('id')->get()
                ->map(fn (Message $message): array => [
                    'conversation' => $message->conversation_id,
                    'texte' => $message->body,
                    'envoye_le' => $message->created_at?->toIso8601String(),
                ])->all(),
            'liens' => Relationship::query()
                ->where(fn ($query) => $query->whereIn('character_id', $characters->modelKeys())->orWhereIn('target_character_id', $characters->modelKeys()))
                ->with(['character', 'target'])->get()
                ->map(fn (Relationship $relationship): array => [
                    'entre' => $relationship->character?->fullName().' et '.$relationship->target?->fullName(),
                    'lien' => $relationship->label(),
                    'statut' => $relationship->status->getLabel(),
                    'ma_description' => $characters->contains('id', $relationship->character_id) ? $relationship->description : $relationship->target_description,
                ])->all(),
            'demandes_a_l_equipe' => ContactTicket::query()->where('user_id', $user->getKey())->with('publicMessages')->get()
                ->map(fn (ContactTicket $ticket): array => [
                    'reference' => $ticket->reference,
                    'sujet' => $ticket->subject,
                    'echanges' => $ticket->publicMessages->map(fn ($message) => [
                        'de' => $message->author === 'equipe' ? 'Équipe' : 'Moi',
                        'texte' => $message->body,
                        'le' => $message->created_at?->toIso8601String(),
                    ])->all(),
                ])->all(),
        ];
    }

    /**
     * Supprime le compte. $eraseTexts : effacer aussi réponses, publications et messages.
     */
    public function delete(User $user, bool $eraseTexts): void
    {
        DB::transaction(function () use ($user, $eraseTexts): void {
            foreach (Character::withTrashed()->where('user_id', $user->getKey())->get() as $character) {
                $this->workflow->releaseResources($character, null, 'Compte supprimé');
                $character->forceDelete();
            }

            FaceclaimClaim::query()
                ->where('user_id', $user->getKey())
                ->whereIn('status', ClaimStatus::ACTIVE)
                ->get()
                ->each(fn (FaceclaimClaim $claim) => $this->registry->release($claim, null, 'Compte supprimé'));

            if ($eraseTexts) {
                $this->eraseTexts($user);
            }

            // Les annonces servent à contacter le joueur : elles partent avec le compte.
            ClassifiedAd::withTrashed()->where('user_id', $user->getKey())->forceDelete();

            ContactTicket::query()->where('user_id', $user->getKey())->delete();
            EventParticipant::query()->where('user_id', $user->getKey())->delete();

            $user->roles()->detach();
            $user->delete();
        });

        // Aucune donnée personnelle dans le journal : seulement le fait.
        ActivityLogger::record('compte.supprime', null, $eraseTexts ? 'Compte supprimé, textes effacés' : 'Compte supprimé, textes anonymisés');
    }

    private function eraseTexts(User $user): void
    {
        Post::withTrashed()->where('user_id', $user->getKey())->get()->each(function (Post $post): void {
            $scene = Scene::withTrashed()->find($post->scene_id);

            if ($scene && ! $post->trashed()) {
                $scene->forceFill([
                    'posts_count' => max(0, $scene->posts_count - 1),
                    'words_count' => max(0, $scene->words_count - $post->word_count),
                ])->save();
            }

            $post->forceDelete();
        });

        FeedComment::withTrashed()->where('user_id', $user->getKey())->get()->each(function (FeedComment $comment): void {
            if (! $comment->trashed()) {
                FeedPost::withTrashed()->whereKey($comment->feed_post_id)->where('comments_count', '>', 0)->decrement('comments_count');
            }

            $comment->forceDelete();
        });

        FeedPost::withTrashed()->where('user_id', $user->getKey())->get()->each->forceDelete();
        Message::withTrashed()->where('user_id', $user->getKey())->forceDelete();
    }
}
