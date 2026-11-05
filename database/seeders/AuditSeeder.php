<?php

namespace Database\Seeders;

use App\Enums\CharacterStatus;
use App\Enums\EventStatus;
use App\Enums\FeedKind;
use App\Models\Character;
use App\Models\Event;
use App\Models\FeedPost;
use App\Models\Location;
use App\Models\Species;
use App\Models\User;
use App\Services\SceneService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Données factices pour l'audit d'accessibilité automatisé (intégration continue uniquement).
 * Jamais lancé par deploy.sh : `php artisan db:seed --class=AuditSeeder`.
 * Les textes sont de simples marqueurs entre crochets, pas de contenu de jeu.
 */
class AuditSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('AuditSeeder est réservé aux tests.');

            return;
        }

        $users = collect(['Auditeur', 'Partenaire'])->map(function (string $pseudo): User {
            $user = User::query()->firstOrNew(['email' => mb_strtolower($pseudo).'@audit.test']);
            $user->forceFill([
                'pseudo' => $pseudo,
                'password' => Hash::make('audit-accessibilite'),
                'email_verified_at' => now(),
                'age_confirmed_at' => now(),
                'terms_accepted_at' => now(),
            ])->save();
            $user->assignRole('joueur');

            return $user;
        });

        $characters = $users->map(function (User $user, int $index): Character {
            $character = new Character([
                'first_name' => '[Personnage '.($index + 1).']',
                'last_name' => '[Nom]',
                'age' => 30,
                'species_id' => Species::query()->value('id'),
                'profession' => '[Profession]',
                'quote' => '[Citation]',
                'history' => '[Histoire de test.]',
                'personality' => '[Caractère de test.]',
                'dialogue_color' => $index === 0 ? 'lanterne' : 'lilas',
            ]);
            $character->forceFill(['user_id' => $user->id, 'status' => CharacterStatus::Accepted, 'accepted_at' => now()])->save();

            return $character;
        });

        $scene = app(SceneService::class)->create($users[0], $characters[0], [
            'title' => '[Scène de test]',
            'rp_date' => '[Date RP]',
            'location_id' => Location::query()->where('is_published', true)->value('id'),
            'visibility' => 'publique',
            'is_open' => true,
            'content_warnings' => ['horreur'],
            'turn_mode' => 'libre',
        ], [$characters[1]->id], "[Paragraphe de test.] « [Réplique de test] »\n\n> [Citation]\n\n---\n\n[Second paragraphe.]");

        app(SceneService::class)->acceptInvitation($scene->participantFor($users[1]), $characters[1]);
        app(SceneService::class)->reply($scene, $users[1], '[Réponse de test.] « [Réplique] »');

        $post = new FeedPost;
        $post->forceFill([
            'user_id' => $users[0]->id,
            'character_id' => $characters[0]->id,
            'character_name' => $characters[0]->fullName(),
            'kind' => FeedKind::Thought,
            'body' => '[Publication de test.]',
        ])->save();

        Event::query()->create([
            'title' => '[Événement de test]',
            'summary' => '[Résumé de test]',
            'status' => EventStatus::Ongoing,
            'is_published' => true,
        ]);
    }
}
