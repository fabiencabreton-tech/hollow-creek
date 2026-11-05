<?php

namespace Tests\Feature;

use App\Enums\CharacterStatus;
use App\Enums\ParticipantStatus;
use App\Enums\SceneStatus;
use App\Models\Character;
use App\Models\Location;
use App\Models\Post;
use App\Models\Scene;
use App\Models\SceneDraft;
use App\Models\Species;
use App\Models\User;
use App\Notifications\SceneInvitation;
use App\Notifications\ScenePostPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SceneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    #[Test]
    public function un_joueur_ouvre_une_scene_publique_et_invite_un_partenaire(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        [$bruno, $bram] = $this->playerWithCharacter('Bram', 'Holloway');
        $location = Location::query()->where('is_published', true)->firstOrFail();

        $this->actingAs($alice)->get(route('scenes.create'))->assertOk()->assertSee('Bram Holloway');

        $response = $this->actingAs($alice)->post(route('scenes.store'), [
            'character_id' => $ada->id,
            'title' => 'Brouillard sur le lac',
            'rp_date' => 'Mardi 3 novembre 2026, 22 h',
            'location_id' => $location->id,
            'visibility' => 'publique',
            'turn_mode' => 'libre',
            'content_warnings' => ['horreur'],
            'partners' => [$bram->id],
            'introduction' => 'La brume montait du lac. « Qui est là ? » murmura Ada.',
        ]);

        $scene = Scene::query()->where('title', 'Brouillard sur le lac')->firstOrFail();
        $response->assertRedirect(route('scenes.show', $scene));

        $this->assertSame(1, $scene->posts_count);
        $this->assertSame(ParticipantStatus::Invited, $scene->participantFor($bruno)->status);
        Notification::assertSentTo($bruno, SceneInvitation::class);

        auth()->guard('web')->logout();
        $this->get(route('scenes.show', $scene))
            ->assertOk()
            ->assertSee('Brouillard sur le lac')
            ->assertSee('Horreur')
            ->assertSee('<span class="dialogue">« Qui est là ? »</span>', false);

        $this->get(route('scenes.index'))->assertOk()->assertSee('Brouillard sur le lac');
        $this->get(route('locations.show', $location))->assertOk()->assertSee('Brouillard sur le lac');
        $this->get(route('home'))->assertOk()->assertSee('Brouillard sur le lac');
        $this->get(route('characters.show', $ada))->assertOk()->assertSee('Brouillard sur le lac');
    }

    #[Test]
    public function une_scene_privee_n_existe_que_pour_ses_participants(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        [$bruno, $bram] = $this->playerWithCharacter('Bram', 'Holloway');
        $scene = $this->openScene($alice, $ada, ['visibility' => 'privee', 'title' => 'Secret de famille'], [$bram->id]);

        auth()->guard('web')->logout();
        $this->get(route('scenes.show', $scene))->assertNotFound();
        $this->get(route('scenes.export', $scene))->assertNotFound();
        $this->get(route('scenes.index'))->assertDontSee('Secret de famille');
        $this->get(route('characters.show', $ada))->assertDontSee('Secret de famille');

        $stranger = $this->player();
        $this->actingAs($stranger)->get(route('scenes.show', $scene))->assertNotFound();
        $this->actingAs($stranger)->post(route('scenes.reply', $scene), ['body' => 'Intrusion'])->assertNotFound();
        $this->actingAs($stranger)->put(route('scenes.draft', $scene), ['body' => 'Intrusion'])->assertForbidden();

        $this->actingAs($bruno)->get(route('scenes.show', $scene))->assertOk()->assertSee('Vous êtes invité');
        $this->actingAs($alice)->get(route('scenes.show', $scene))->assertOk();
        $this->actingAs($this->staff('administrateur'))->get(route('scenes.show', $scene))->assertOk();
        $this->actingAs($this->staff('moderateur'))->get(route('scenes.show', $scene))->assertNotFound();

        $this->actingAs($bruno)->post(route('scenes.invitations.decline', [$scene, $scene->participantFor($bruno)]))->assertRedirect();
        $this->actingAs($bruno)->get(route('scenes.show', $scene))->assertNotFound();
    }

    #[Test]
    public function l_invite_accepte_avec_le_personnage_de_son_choix_et_repond(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        [$bruno, $bram] = $this->playerWithCharacter('Bram', 'Holloway');
        $bea = $this->acceptedCharacter($bruno, 'Béatrice', 'Holloway');
        $scene = $this->openScene($alice, $ada, [], [$bram->id]);
        $invitation = $scene->participantFor($bruno);

        $this->actingAs($bruno)->post(route('scenes.reply', $scene), ['body' => 'Trop tôt'])->assertForbidden();

        $this->actingAs($bruno)
            ->post(route('scenes.invitations.accept', [$scene, $invitation]), ['character_id' => $bea->id])
            ->assertRedirect(route('scenes.show', $scene));

        $this->assertSame($bea->id, $invitation->fresh()->character_id);

        $this->actingAs($bruno)
            ->post(route('scenes.reply', $scene), ['body' => 'Béatrice leva sa lanterne.'])
            ->assertRedirect();

        $post = Post::query()->latest('id')->firstOrFail();
        $this->assertSame('Béatrice Holloway', $post->character_name);
        $this->assertSame(2, $scene->fresh()->posts_count);
        Notification::assertSentTo($alice, ScenePostPublished::class);
        Notification::assertNotSentTo($bruno, ScenePostPublished::class);
    }

    #[Test]
    public function on_ne_rejoint_pas_avec_le_personnage_d_un_autre(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        [$bruno, $bram] = $this->playerWithCharacter('Bram', 'Holloway');
        $scene = $this->openScene($alice, $ada, [], [$bram->id]);

        $this->actingAs($bruno)
            ->post(route('scenes.invitations.accept', [$scene, $scene->participantFor($bruno)]), ['character_id' => $ada->id])
            ->assertSessionHasErrors('character_id');

        $this->assertSame(ParticipantStatus::Invited, $scene->participantFor($bruno)->status);
    }

    #[Test]
    public function le_tour_par_tour_respecte_l_ordre_de_passage(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        [$bruno, $bram] = $this->playerWithCharacter('Bram', 'Holloway');
        $scene = $this->openScene($alice, $ada, ['turn_mode' => 'tour'], [$bram->id]);

        $this->actingAs($bruno)->post(route('scenes.invitations.accept', [$scene, $scene->participantFor($bruno)]), ['character_id' => $bram->id]);
        $this->assertSame($bruno->id, $scene->fresh()->next_user_id);

        $this->actingAs($alice)->post(route('scenes.reply', $scene), ['body' => 'Pas mon tour'])->assertForbidden();
        $this->actingAs($bruno)->post(route('scenes.reply', $scene), ['body' => 'À moi.'])->assertRedirect();

        $this->assertSame($alice->id, $scene->fresh()->next_user_id);
        Notification::assertSentTo($alice, ScenePostPublished::class, fn (ScenePostPublished $n): bool => $n->isTheirTurn);
        $this->actingAs($alice)->get(route('scenes.mine'))->assertOk()->assertSee('À vous');
    }

    #[Test]
    public function les_preferences_d_e_mail_sont_respectees(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        [$bruno, $bram] = $this->playerWithCharacter('Bram', 'Holloway');
        $scene = $this->openScene($alice, $ada, [], [$bram->id]);
        $this->actingAs($bruno)->post(route('scenes.invitations.accept', [$scene, $scene->participantFor($bruno)]), ['character_id' => $bram->id]);

        $this->actingAs($alice)->put(route('account.preferences'), ['scene_mail' => 'aucune'])->assertRedirect();
        $this->assertSame('aucune', $alice->fresh()->scene_mail);

        $this->actingAs($bruno)->post(route('scenes.reply', $scene), ['body' => 'Silence.'])->assertRedirect();

        Notification::assertNotSentTo($alice, ScenePostPublished::class);
    }

    #[Test]
    public function le_brouillon_s_enregistre_puis_disparait_a_la_publication(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        $scene = $this->openScene($alice, $ada);

        $this->actingAs($alice)
            ->putJson(route('scenes.draft', $scene), ['body' => 'Début de réponse'])
            ->assertOk()
            ->assertJson(['saved' => true]);

        $this->assertSame('Début de réponse', SceneDraft::query()->where('user_id', $alice->id)->value('body'));
        $this->actingAs($alice)->get(route('scenes.show', $scene))->assertSee('Début de réponse');
        $this->actingAs($alice)->get(route('scenes.mine'))->assertSee('Brouillons à reprendre');

        $this->actingAs($alice)->post(route('scenes.reply', $scene), ['body' => 'Réponse complète'])->assertRedirect();
        $this->assertSame(0, SceneDraft::query()->count());

        // Une sauvegarde automatique arrivée après la publication ne recrée pas le brouillon.
        $this->actingAs($alice)->putJson(route('scenes.draft', $scene), ['body' => 'Réponse complète'])->assertOk();
        $this->assertSame(0, SceneDraft::query()->count());

        $this->actingAs($alice)
            ->postJson(route('scenes.preview'), ['body' => '**gras** <script>alert(1)</script>'])
            ->assertOk()
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, '<strong>gras</strong>') && ! str_contains($html, '<script>'));
    }

    #[Test]
    public function l_apercu_sans_javascript_garde_le_texte(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        $scene = $this->openScene($alice, $ada);

        $this->actingAs($alice)
            ->post(route('scenes.reply', $scene), ['body' => 'Un *essai*', 'action' => 'apercu'])
            ->assertRedirect();

        $this->assertSame(1, $scene->fresh()->posts_count);
        $this->actingAs($alice)->withSession(['preview' => true])->get(route('scenes.show', $scene))->assertSee('<em>essai</em>', false);
    }

    #[Test]
    public function l_auteur_modifie_sa_reponse_et_la_version_precedente_est_gardee(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        $scene = $this->openScene($alice, $ada);
        $post = $scene->posts()->firstOrFail();

        $this->actingAs($this->player())->get(route('scenes.posts.edit', [$scene, $post]))->assertForbidden();

        $this->actingAs($alice)->get(route('scenes.posts.edit', [$scene, $post]))->assertOk();
        $this->actingAs($alice)->put(route('scenes.posts.update', [$scene, $post]), ['body' => 'Texte corrigé.'])->assertRedirect();

        $post->refresh();
        $this->assertSame('Texte corrigé.', $post->body);
        $this->assertNotNull($post->edited_at);
        $this->assertSame(1, $post->revisions()->count());
        $this->get(route('scenes.show', $scene))->assertSee('modifiée le');

        $other = Scene::query()->create(['title' => 'Autre', 'visibility' => 'publique', 'turn_mode' => 'libre']);
        $this->actingAs($alice)->get('/scenes/'.$other->id.'/reponses/'.$post->id.'/modifier')->assertNotFound();
    }

    #[Test]
    public function une_scene_terminee_ne_recoit_plus_de_reponse_et_s_exporte(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        $scene = $this->openScene($alice, $ada, ['title' => 'La dernière lanterne']);

        $this->actingAs($alice)->get(route('scenes.export', $scene))->assertForbidden();
        $this->actingAs($this->player())->post(route('scenes.status', $scene), ['status' => 'terminee'])->assertForbidden();

        $this->actingAs($alice)->post(route('scenes.status', $scene), ['status' => 'terminee'])->assertRedirect();
        $this->assertSame(SceneStatus::Finished, $scene->fresh()->status);
        $this->assertNotNull($scene->fresh()->finished_at);

        $this->actingAs($alice)->post(route('scenes.reply', $scene), ['body' => 'Encore'])->assertForbidden();
        $this->actingAs($alice)->get(route('scenes.posts.edit', [$scene, $scene->posts()->first()]))->assertForbidden();

        auth()->guard('web')->logout();
        $this->get(route('scenes.export', $scene))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="hollow-creek-scene-'.$scene->id.'-la-derniere-lanterne.html"')
            ->assertSee('La dernière lanterne');

        $this->get(route('characters.show', $ada))->assertSee('RP terminés')->assertSee('La dernière lanterne');
    }

    #[Test]
    public function une_scene_ouverte_se_rejoint_et_se_quitte(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        [$bruno, $bram] = $this->playerWithCharacter('Bram', 'Holloway');
        $closed = $this->openScene($alice, $ada, ['title' => 'Fermée']);
        $open = $this->openScene($alice, $ada, ['title' => 'Ouverte', 'is_open' => '1']);

        $this->actingAs($bruno)->post(route('scenes.join', $closed), ['character_id' => $bram->id])->assertForbidden();
        $this->actingAs($bruno)->post(route('scenes.join', $open), ['character_id' => $bram->id])->assertRedirect(route('scenes.show', $open));
        $this->assertTrue($open->fresh()->isActiveParticipant($bruno));

        $this->actingAs($alice)->post(route('scenes.leave', $open))->assertForbidden();
        $this->actingAs($bruno)->post(route('scenes.leave', $open))->assertRedirect();
        $this->assertSame(ParticipantStatus::Left, $open->fresh()->participantFor($bruno)->status);
    }

    #[Test]
    public function il_faut_un_personnage_accepte_pour_ouvrir_une_scene(): void
    {
        $player = $this->player();

        $this->actingAs($player)->get(route('scenes.create'))->assertRedirect(route('characters.mine'));
        $this->actingAs($player)->post(route('scenes.store'), ['title' => 'Essai'])->assertForbidden();
    }

    #[Test]
    public function le_lien_non_lu_mene_a_la_premiere_reponse_non_lue(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        [$bruno, $bram] = $this->playerWithCharacter('Bram', 'Holloway');
        $scene = $this->openScene($alice, $ada, [], [$bram->id]);
        $this->actingAs($bruno)->post(route('scenes.invitations.accept', [$scene, $scene->participantFor($bruno)]), ['character_id' => $bram->id]);
        $this->actingAs($bruno)->get(route('scenes.show', $scene));

        $this->actingAs($alice)->post(route('scenes.reply', $scene), ['body' => 'Nouvelle réponse']);
        $newPost = Post::query()->latest('id')->firstOrFail();

        $this->actingAs($bruno)->get(route('scenes.unread', $scene))
            ->assertRedirect(route('scenes.show', ['scene' => $scene, 'page' => 1]).'#reponse-'.$newPost->id);
    }

    #[Test]
    public function le_html_saisi_est_neutralise(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        $scene = $this->openScene($alice, $ada);

        $this->actingAs($alice)->post(route('scenes.reply', $scene), ['body' => '<img src=x onerror=alert(1)> « Bonsoir »']);

        $this->get(route('scenes.show', $scene))
            ->assertDontSee('<img src=x', false)
            ->assertSee('&lt;img', false);
    }

    #[Test]
    public function la_couleur_de_dialogue_se_choisit_par_personnage(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');

        $this->actingAs($alice)->put(route('characters.color', $ada), ['dialogue_color' => 'lilas'])->assertRedirect();
        $this->assertSame('lilas', $ada->fresh()->dialogue_color);

        $this->actingAs($alice)->put(route('characters.color', $ada), ['dialogue_color' => 'fuchsia'])->assertSessionHasErrors('dialogue_color');
        $this->actingAs($this->player())->put(route('characters.color', $ada), ['dialogue_color' => 'vin'])->assertForbidden();

        $scene = $this->openScene($alice, $ada->fresh());
        $this->get(route('scenes.show', $scene))->assertSee('post--c-lilas', false);
    }

    /**
     * @return array{0: User, 1: Character}
     */
    private function playerWithCharacter(string $first, string $last): array
    {
        $user = $this->player();

        return [$user, $this->acceptedCharacter($user, $first, $last)];
    }

    private function acceptedCharacter(User $user, string $first, string $last): Character
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

    /**
     * @param  array<string, mixed>  $overrides
     * @param  array<int, int>  $partners
     */
    private function openScene(User $user, Character $character, array $overrides = [], array $partners = []): Scene
    {
        $this->actingAs($user)->post(route('scenes.store'), array_merge([
            'character_id' => $character->id,
            'title' => 'Une soirée à Alderwick',
            'rp_date' => 'Jeudi 5 novembre 2026',
            'visibility' => 'publique',
            'turn_mode' => 'libre',
            'partners' => $partners,
            'introduction' => 'Il pleuvait sur Old Creek.',
        ], $overrides))->assertSessionHasNoErrors();

        return Scene::query()->latest('id')->firstOrFail();
    }
}
