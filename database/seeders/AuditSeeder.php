<?php

namespace Database\Seeders;

use App\Enums\CharacterStatus;
use App\Enums\EventStatus;
use App\Enums\FeedKind;
use App\Enums\GazetteRubric;
use App\Models\Character;
use App\Models\ClassifiedAd;
use App\Models\Clue;
use App\Models\District;
use App\Models\Event;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyPrelink;
use App\Models\FeedPost;
use App\Models\GazetteArticle;
use App\Models\Investigation;
use App\Models\JournalEntry;
use App\Models\KirisawaVictim;
use App\Models\Location;
use App\Models\MunicipalRecord;
use App\Models\Residence;
use App\Models\Species;
use App\Models\User;
use App\Services\FamilyService;
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
            $character->forceFill(['slug' => 'audit-'.($index + 1), 'user_id' => $user->id, 'status' => CharacterStatus::Accepted, 'accepted_at' => now()])->save();

            return $character;
        });

        KirisawaVictim::query()->create(['number' => 1, 'first_name' => '[Prénom]', 'last_name' => '[Nom]', 'role' => 'patient', 'age_at_death' => 30, 'summary' => '[Présentation de test.]', 'status' => 'ouvert', 'is_published' => true]);

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

        // Familles : un arbre, un prélien.
        $minase = Family::query()->where('slug', 'minase')->first();
        if ($minase) {
            $characters[0]->forceFill(['family_id' => $minase->id])->save();
            app(FamilyService::class)->ensureMember($characters[0]);
            $ancestor = FamilyMember::query()->create(['family_id' => $minase->id, 'name' => '[Ancêtre]', 'is_deceased' => true]);
            FamilyMember::query()->where('character_id', $characters[0]->id)->update(['parent_id' => $ancestor->id, 'note' => '[Mention]']);
            $prelink = new FamilyPrelink;
            $prelink->forceFill(['family_id' => $minase->id, 'character_id' => $characters[0]->id, 'user_id' => $users[0]->id, 'relation' => 'fratrie', 'title' => '[Prélien de test]', 'description' => '[Description de test du prélien familial.]', 'status' => 'ouvert'])->save();
        }

        // Immersion
        MunicipalRecord::query()->create(['slug' => 'audit-archive', 'title' => '[Document de test]', 'category' => 'conseil', 'reference' => '[Cote]', 'document_date' => '1996-11-02', 'excerpt' => '[Résumé de test.]', 'body' => '[Texte de test.]', 'is_published' => true]);

        $residence = Residence::query()->create(['slug' => 'audit-habitation', 'name' => '[Habitation de test]', 'kind' => 'maison', 'district_id' => District::query()->value('id'), 'capacity' => 3, 'summary' => '[Résumé de test.]', 'is_published' => true]);
        $characters[1]->forceFill(['residence_id' => $residence->id])->save();

        $investigation = Investigation::query()->create(['slug' => 'audit-enquete', 'title' => '[Enquête de test]', 'summary' => '[Résumé de test.]', 'body' => '[Présentation de test.]', 'is_published' => true]);
        Clue::query()->create(['investigation_id' => $investigation->id, 'title' => '[Indice public]', 'body' => '[Texte de test.]', 'is_public' => true]);
        $secret = Clue::query()->create(['investigation_id' => $investigation->id, 'title' => '[Indice confié]', 'body' => '[Texte de test.]', 'is_public' => false]);
        $secret->characters()->attach($characters[0]->id);

        $ad = new ClassifiedAd;
        $ad->forceFill(['user_id' => $users[1]->id, 'character_id' => $characters[1]->id, 'character_name' => $characters[1]->fullName(), 'category' => 'vente', 'title' => '[Annonce de test]', 'body' => '[Texte de test.]', 'contact' => '[Contact]', 'expires_at' => now()->addDays(14)])->save();

        foreach ([false, true] as $private) {
            $entry = new JournalEntry;
            $entry->forceFill(['character_id' => $characters[0]->id, 'user_id' => $users[0]->id, 'title' => $private ? '[Entrée privée]' : '[Entrée publique]', 'rp_date' => '[Date RP]', 'body' => '[Texte de test.]', 'is_private' => $private])->save();
        }

        Event::query()->create([
            'title' => '[Événement de test]',
            'summary' => '[Résumé de test]',
            'status' => EventStatus::Ongoing,
            'is_published' => true,
        ]);

        // Gazette : une Une, un dossier, plusieurs rubriques et années (textes de test).
        $articles = [
            ['audit-une', '[Article à la une]', GazetteRubric::Actualites, '[Jour] 2 novembre 2026', true, '[Dossier de test]'],
            ['audit-fait-divers', '[Fait divers de test]', GazetteRubric::FaitsDivers, '[Jour] 3 novembre 2026', false, '[Dossier de test]'],
            ['audit-rumeur', '[Rumeur de test]', GazetteRubric::Rumeurs, '[Jour] 1er novembre 2026', false, null],
            ['audit-vie-locale', '[Vie locale de test]', GazetteRubric::VieLocale, '[Jour] 20 octobre 2026', false, null],
            ['audit-archive-1996', '[Article ancien de test]', GazetteRubric::AvisDeces, '[Jour] 2 novembre 1996', false, null],
        ];
        foreach ($articles as $index => [$slug, $title, $rubric, $date, $featured, $dossier]) {
            GazetteArticle::query()->create([
                'slug' => $slug,
                'title' => $title,
                'rubric' => $rubric,
                'dossier' => $dossier,
                'byline' => 'La rédaction',
                'rp_date' => $date,
                'excerpt' => '[Chapeau de test : quelques lignes pour présenter l’article.]',
                'body' => "[Premier paragraphe de test.]\n\n[Second paragraphe de test.]",
                'is_featured' => $featured,
                'status' => GazetteArticle::STATUS_PUBLISHED,
                'published_at' => now()->subHours(10 - $index),
            ]);
        }
    }
}
