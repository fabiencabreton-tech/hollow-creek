<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\RelationshipStatus;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Reports\Pages\ViewReport;
use App\Jobs\AnnounceEvent;
use App\Models\Event;
use App\Models\FeedPost;
use App\Models\Post;
use App\Models\Relationship;
use App\Models\Report;
use App\Models\Scene;
use App\Support\ImageStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\MakesCharacters;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use MakesCharacters, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake(ImageStore::DISK);
    }

    #[Test]
    public function un_lien_s_affiche_sur_les_deux_fiches_une_fois_accepte(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        [$bruno, $bram] = $this->playerWithCharacter('Bram', 'Holloway');

        $this->actingAs($alice)->post(route('relationships.store'), [
            'character_id' => $ada->id,
            'target_character_id' => $bram->id,
            'kind' => 'rivalite',
            'label' => 'Dette de jeu',
            'description' => 'Ada ne lui pardonnera jamais.',
        ])->assertRedirect(route('relationships.index'));

        $relationship = Relationship::query()->firstOrFail();
        $this->get(route('characters.show', $bram))->assertDontSee('Dette de jeu');
        $this->assertDatabaseHas('user_notifications', ['user_id' => $bruno->id, 'type' => 'relation']);

        $this->actingAs($alice)->post(route('relationships.accept', $relationship))->assertSessionHasErrors('relationship');
        $this->actingAs($bruno)->post(route('relationships.accept', $relationship), ['description' => 'Bram rit encore de cette nuit-là.'])->assertRedirect();

        $this->get(route('characters.show', $bram))->assertSee('Dette de jeu')->assertSee('Bram rit encore de cette nuit-là.');
        $this->get(route('characters.show', $ada))->assertSee('Dette de jeu')->assertSee('Ada ne lui pardonnera jamais.');

        $this->actingAs($alice)->post(route('relationships.store'), [
            'character_id' => $ada->id, 'target_character_id' => $bram->id, 'kind' => 'amitie',
        ])->assertSessionHasErrors('target_character_id');

        $this->actingAs($this->player())->post(route('relationships.end', $relationship))->assertSessionHasErrors('relationship');
        $this->actingAs($bruno)->post(route('relationships.end', $relationship))->assertRedirect();
        $this->assertSame(RelationshipStatus::Ended, $relationship->fresh()->status);
        $this->get(route('characters.show', $ada))->assertDontSee('Dette de jeu');
    }

    #[Test]
    public function un_blocage_empeche_les_propositions_de_lien_et_les_invitations(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        [$bruno, $bram] = $this->playerWithCharacter('Bram', 'Holloway');
        $this->actingAs($bruno)->post(route('blocks.store'), ['pseudo' => $alice->pseudo]);

        $this->actingAs($alice)->post(route('relationships.store'), [
            'character_id' => $ada->id, 'target_character_id' => $bram->id, 'kind' => 'amitie',
        ])->assertSessionHasErrors('target_character_id');

        $this->actingAs($alice)->get(route('scenes.create'))->assertOk()->assertDontSee('Bram Holloway');
    }

    #[Test]
    public function les_murmures_se_publient_et_se_commentent(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        [$bruno, $bram] = $this->playerWithCharacter('Bram', 'Holloway');

        $this->actingAs($alice)->post(route('feed.store'), ['character_id' => $ada->id, 'kind' => 'pensee', 'body' => 'Le lac était trop calme ce soir.'])->assertRedirect();
        $this->actingAs($alice)->post(route('feed.store'), [
            'character_id' => $ada->id,
            'kind' => 'image',
            'image' => UploadedFile::fake()->image('lac.jpg', 800, 600),
        ])->assertSessionHasErrors('image_credit');
        $this->actingAs($alice)->post(route('feed.store'), ['character_id' => $bram->id, 'kind' => 'pensee', 'body' => 'Usurpation'])->assertSessionHasErrors('character_id');

        $post = FeedPost::query()->firstOrFail();
        $this->get(route('feed.index'))->assertOk()->assertSee('Le lac était trop calme ce soir.');

        $this->actingAs($bruno)->post(route('feed.comment', $post), ['character_id' => $bram->id, 'body' => 'Trop calme, oui.'])->assertRedirect();
        $this->assertSame(1, $post->fresh()->comments_count);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $alice->id, 'type' => 'murmure']);
        $this->get(route('feed.show', $post))->assertSee('Trop calme, oui.');
        $this->get(route('characters.show', $ada))->assertSee('Le lac était trop calme ce soir.');

        $this->actingAs($bruno)->delete(route('feed.destroy', $post))->assertForbidden();
        $this->actingAs($alice)->delete(route('feed.destroy', $post))->assertRedirect();
        $this->assertSoftDeleted($post);
    }

    #[Test]
    public function un_signalement_ne_transmet_que_l_extrait_et_l_equipe_peut_masquer(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        $this->actingAs($alice)->post(route('scenes.store'), [
            'character_id' => $ada->id, 'title' => 'Sur le pont', 'rp_date' => 'Jeudi', 'visibility' => 'publique',
            'turn_mode' => 'libre', 'introduction' => 'Un texte qui pose problème.',
        ]);
        $post = Post::query()->firstOrFail();
        $reporter = $this->player();

        $this->actingAs($alice)->get(route('reports.create', ['type' => 'reponse', 'id' => $post->id]))->assertNotFound();
        $this->actingAs($reporter)->get(route('reports.create', ['type' => 'reponse', 'id' => $post->id]))->assertOk()->assertSee('Un texte qui pose problème.');
        $this->actingAs($reporter)->post(route('reports.store'), ['type' => 'reponse', 'id' => $post->id, 'reason' => 'ia', 'details' => 'Style très mécanique.'])->assertRedirect();

        $report = Report::query()->firstOrFail();
        $this->assertSame($alice->id, $report->reported_user_id);
        $this->assertStringContainsString('Un texte qui pose problème.', $report->snapshot);

        // Une scène privée reste inaccessible, même pour la signaler.
        $private = Scene::query()->firstOrFail();
        $private->forceFill(['visibility' => 'privee'])->save();
        $this->actingAs($this->player())->get(route('reports.create', ['type' => 'reponse', 'id' => $post->id]))->assertNotFound();

        $moderator = $this->staff('moderateur');
        $this->actingAs($moderator)->get('/admin/signalements')->assertOk();

        Livewire::actingAs($moderator)
            ->test(ViewReport::class, ['record' => $report->id])
            ->callAction('hide')
            ->callAction('handle', ['resolution' => 'Réponse masquée, joueur averti.'])
            ->assertHasNoActionErrors();

        $this->assertSoftDeleted($post);
        $this->assertSame(Report::STATUS_HANDLED, $report->fresh()->status);
        $this->actingAs($this->player())->get('/admin/signalements')->assertForbidden();
    }

    #[Test]
    public function un_evenement_publie_previent_les_joueurs_et_accueille_des_inscriptions(): void
    {
        Bus::fake([AnnounceEvent::class]);
        $admin = $this->staff('administrateur');

        Livewire::actingAs($admin)
            ->test(CreateEvent::class)
            ->fillForm([
                'title' => 'La Nuit des Lanternes',
                'summary' => 'Quarante-trois lanternes sur le lac.',
                'status' => EventStatus::Announced->value,
                'registrations_open' => true,
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $event = Event::query()->firstOrFail();
        Bus::assertDispatched(AnnounceEvent::class);

        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        (new AnnounceEvent($event))->handle(app(\App\Services\Notifier::class));
        $this->assertDatabaseHas('user_notifications', ['user_id' => $alice->id, 'type' => 'evenement']);

        $this->get(route('events.index'))->assertOk()->assertSee('La Nuit des Lanternes');
        $this->actingAs($alice)->post(route('events.register', $event), ['character_id' => $ada->id])->assertRedirect();
        $this->get(route('events.show', $event))->assertSee('Ada Ashby');

        $this->actingAs($alice)->post(route('scenes.store'), [
            'character_id' => $ada->id, 'event_id' => $event->id, 'title' => 'Lanternes sur l’eau', 'rp_date' => 'Samedi',
            'visibility' => 'publique', 'turn_mode' => 'libre', 'introduction' => 'Les lanternes montaient.',
        ])->assertSessionHasNoErrors();

        $this->get(route('events.show', $event))->assertSee('Lanternes sur l’eau');
        $this->get(route('home'))->assertSee('La Nuit des Lanternes');

        $draft = Event::query()->create(['title' => 'Brouillon secret', 'status' => 'annonce']);
        $this->get(route('events.show', $draft))->assertNotFound();
    }

    #[Test]
    public function le_profil_se_personnalise(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');

        $this->actingAs($this->player())->get(route('characters.profile.edit', $ada))->assertForbidden();

        $this->actingAs($alice)->put(route('characters.profile.update', $ada), [
            'banner' => UploadedFile::fake()->image('banniere.jpg', 1600, 500),
            'profile_layout' => 'journal',
        ])->assertSessionHasErrors('banner_credit');

        $this->actingAs($alice)->put(route('characters.profile.update', $ada), [
            'banner' => UploadedFile::fake()->image('banniere.jpg', 1600, 500),
            'banner_credit' => 'Photo de l’auteur, CC BY',
            'accent_color' => 'lilas',
            'profile_layout' => 'journal',
            'absence_note' => 'En voyage jusqu’à la Toussaint.',
        ])->assertRedirect(route('characters.show', $ada));

        $ada->refresh();
        $this->assertNotNull($ada->banner_path);
        Storage::disk(ImageStore::DISK)->assertExists($ada->banner_path);

        $this->get(route('characters.show', $ada))
            ->assertSee('profile-page--journal', false)
            ->assertSee('accent--lilas', false)
            ->assertSee('En voyage jusqu’à la Toussaint.');
    }
}
