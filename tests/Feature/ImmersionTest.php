<?php

namespace Tests\Feature;

use App\Filament\Resources\Clues\Pages\CreateClue;
use App\Models\Character;
use App\Models\ClassifiedAd;
use App\Models\Clue;
use App\Models\District;
use App\Models\Investigation;
use App\Models\JournalEntry;
use App\Models\MunicipalRecord;
use App\Models\Report;
use App\Models\Residence;
use App\Models\UserNotification;
use App\Services\AccountData;
use App\Services\CharacterWorkflow;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\MakesCharacters;
use Tests\TestCase;

class ImmersionTest extends TestCase
{
    use MakesCharacters;

    // Archives municipales ----------------------------------------------

    #[Test]
    public function les_archives_publiees_se_consultent_et_se_filtrent(): void
    {
        MunicipalRecord::query()->create(['title' => 'Délibération sur le pont', 'category' => 'conseil', 'reference' => '1 D 4', 'document_date' => '1996-11-03', 'body' => 'Le conseil vote les **travaux**.', 'is_published' => true]);
        MunicipalRecord::query()->create(['title' => 'Acte de naissance', 'category' => 'etat-civil', 'document_date' => '2001-04-02', 'is_published' => true]);
        $draft = MunicipalRecord::query()->create(['title' => 'Rapport non classé', 'category' => 'sante', 'is_published' => false]);

        $this->get('/archives')->assertOk()->assertSee('Délibération sur le pont')->assertSee('Acte de naissance')->assertDontSee('Rapport non classé');
        $this->get('/archives?categorie=conseil')->assertOk()->assertSee('Délibération sur le pont')->assertDontSee('Acte de naissance');
        $this->get('/archives?annee=2001')->assertOk()->assertSee('Acte de naissance')->assertDontSee('Délibération sur le pont');
        $this->get('/archives?q=pont')->assertOk()->assertSee('Délibération sur le pont')->assertDontSee('Acte de naissance');
        $this->get('/archives/deliberation-sur-le-pont')->assertOk()->assertSee('Cote 1 D 4')->assertSee('<strong>travaux</strong>', false);

        $this->get('/archives/'.$draft->slug)->assertNotFound();
        $this->actingAs($this->staff())->get('/archives/'.$draft->slug)->assertOk()->assertSee('Rapport non classé');
    }

    // Petites annonces ----------------------------------------------------

    #[Test]
    public function un_personnage_passe_une_petite_annonce_qui_expire(): void
    {
        [$user, $character] = $this->playerWithCharacter('Hana', 'Mori');

        $this->actingAs($user)->post(route('ads.store'), [
            'character_id' => $character->id,
            'category' => 'vente',
            'title' => 'Vends vélo rouge',
            'body' => 'Bon état, quelques rayures.',
            'contact' => 'Demander Hana au Kissa Komorebi',
            'duration' => 14,
        ])->assertSessionHasNoErrors();

        $ad = ClassifiedAd::query()->firstOrFail();
        $this->assertSame('Hana Mori', $ad->character_name);
        $this->assertTrue($ad->expires_at->between(now()->addDays(13), now()->addDays(15)));

        $this->get('/annonces')->assertOk()->assertSee('Vends vélo rouge')->assertSee('Demander Hana au Kissa Komorebi');
        $this->get('/annonces?rubrique=emploi')->assertOk()->assertDontSee('Vends vélo rouge');

        $ad->forceFill(['expires_at' => now()->subDay()])->save();
        $this->get('/annonces')->assertOk()->assertDontSee('Vends vélo rouge');

        $this->actingAs($user)->post(route('ads.renew', $ad), ['duration' => 7])->assertRedirect();
        $this->assertTrue($ad->fresh()->isActive());
    }

    #[Test]
    public function une_annonce_est_signee_par_son_propre_personnage_et_retiree_par_son_auteur(): void
    {
        [$user, $character] = $this->playerWithCharacter('Hana', 'Mori');
        [$other, $theirs] = $this->playerWithCharacter('Ren', 'Abe');
        $payload = ['category' => 'service', 'title' => 'Cours de calligraphie', 'body' => 'Le samedi.', 'duration' => 7];

        $this->post(route('ads.store'), $payload + ['character_id' => $character->id])->assertRedirect(route('login'));
        $this->actingAs($user)->post(route('ads.store'), $payload + ['character_id' => $theirs->id])->assertSessionHasErrors('character_id');
        $this->actingAs($user)->post(route('ads.store'), $payload + ['character_id' => $character->id])->assertSessionHasNoErrors();

        $ad = ClassifiedAd::query()->firstOrFail();
        $this->actingAs($other)->delete(route('ads.destroy', $ad))->assertForbidden();
        $this->actingAs($other)->post(route('ads.renew', $ad), ['duration' => 7])->assertForbidden();

        $this->actingAs($other)->post(route('reports.store'), [
            'type' => 'annonce',
            'id' => $ad->id,
            'reason' => array_key_first(config('hollow.reports.reasons')),
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, Report::query()->where('reportable_type', 'annonce')->count());

        $this->actingAs($user)->delete(route('ads.destroy', $ad))->assertRedirect();
        $this->assertSoftDeleted($ad);
    }

    #[Test]
    public function le_nombre_d_annonces_en_ligne_est_limite(): void
    {
        [$user, $character] = $this->playerWithCharacter('Hana', 'Mori');

        foreach (range(1, ClassifiedAd::MAX_ACTIVE_PER_USER + 1) as $number) {
            $response = $this->actingAs($user)->post(route('ads.store'), [
                'character_id' => $character->id, 'category' => 'recherche', 'title' => 'Annonce '.$number, 'body' => 'Texte.', 'duration' => 7,
            ]);
        }

        $response->assertSessionHasErrors('title');
        $this->assertSame(ClassifiedAd::MAX_ACTIVE_PER_USER, ClassifiedAd::query()->count());
    }

    // Journaux personnels ---------------------------------------------------

    #[Test]
    public function une_entree_privee_du_journal_ne_quitte_pas_le_serveur(): void
    {
        [$user, $character] = $this->playerWithCharacter('Hana', 'Mori');
        [$other] = $this->playerWithCharacter('Ren', 'Abe');

        $this->actingAs($user)->post(route('journal.store', $character), ['title' => 'Le lac', 'body' => 'Une page que tout le monde peut lire.', 'visibility' => 'public'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('journal.store', $character), ['title' => 'Mon secret', 'body' => 'Je l’ai vue sur le pont Yūgure.', 'visibility' => 'prive'])->assertSessionHasNoErrors();

        $private = JournalEntry::query()->where('is_private', true)->firstOrFail();

        $this->app['auth']->forgetGuards();
        $this->get(route('journal.index', $character))->assertOk()->assertSee('Le lac')->assertDontSee('Mon secret')->assertDontSee('pont Yūgure');
        $this->get(route('journal.show', [$character, $private]))->assertNotFound();

        $this->actingAs($other)->get(route('journal.index', $character))->assertOk()->assertSee('Le lac')->assertDontSee('Mon secret')->assertDontSee('pont Yūgure');
        $this->actingAs($other)->get(route('journal.show', [$character, $private]))->assertNotFound();

        $this->actingAs($user)->get(route('journal.index', $character))->assertOk()->assertSee('Mon secret');
        $this->actingAs($user)->get(route('journal.show', [$character, $private]))->assertOk()->assertSee('pont Yūgure');

        $moderator = $this->staff();
        $this->actingAs($moderator)->get(route('journal.show', [$character, $private]))->assertOk();
    }

    #[Test]
    public function seul_le_joueur_du_personnage_ecrit_et_modifie_son_journal(): void
    {
        [$user, $character] = $this->playerWithCharacter('Hana', 'Mori');
        [$other, $theirs] = $this->playerWithCharacter('Ren', 'Abe');

        $this->actingAs($other)->get(route('journal.create', $character))->assertForbidden();
        $this->actingAs($other)->post(route('journal.store', $character), ['body' => 'Intrusion.', 'visibility' => 'public'])->assertForbidden();

        $this->actingAs($user)->post(route('journal.store', $character), ['body' => 'Première page.', 'visibility' => 'public']);
        $entry = JournalEntry::query()->firstOrFail();

        $this->actingAs($other)->put(route('journal.update', [$character, $entry]), ['body' => 'Modifié.', 'visibility' => 'public'])->assertForbidden();
        $this->actingAs($other)->delete(route('journal.destroy', [$character, $entry]))->assertForbidden();
        // Une entrée ne se consulte que sous son propre personnage.
        $this->get(route('journal.show', [$theirs, $entry]))->assertNotFound();

        $this->actingAs($user)->put(route('journal.update', [$character, $entry]), ['body' => 'Page réécrite.', 'visibility' => 'prive'])->assertRedirect();
        $this->assertTrue($entry->fresh()->is_private);

        $this->actingAs($user)->delete(route('journal.destroy', [$character, $entry]))->assertRedirect();
        $this->assertSoftDeleted($entry);
    }

    // Habitations -------------------------------------------------------------

    #[Test]
    public function on_emmenage_dans_la_limite_des_places(): void
    {
        $residence = Residence::query()->create(['name' => 'Maison Fujii', 'kind' => 'maison', 'district_id' => District::query()->value('id'), 'capacity' => 1, 'is_published' => true]);
        [$user, $character] = $this->playerWithCharacter('Hana', 'Mori');
        [$other, $theirs] = $this->playerWithCharacter('Ren', 'Abe');

        $this->actingAs($user)->post(route('residences.move-in', $residence), ['character_id' => $theirs->id])->assertSessionHasErrors('character_id');
        $this->actingAs($user)->post(route('residences.move-in', $residence), ['character_id' => $character->id])->assertSessionHasNoErrors();
        $this->assertSame($residence->id, $character->fresh()->residence_id);

        $this->actingAs($other)->post(route('residences.move-in', $residence), ['character_id' => $theirs->id])->assertSessionHasErrors('character_id');
        $this->assertNull($theirs->fresh()->residence_id);

        $this->get(route('residences.show', $residence))->assertOk()->assertSee('Hana Mori');
        $this->get(route('characters.show', $character))->assertOk()->assertSee('Maison Fujii');
        $this->get(route('residences.index'))->assertOk()->assertSee('1 / 1');

        $this->actingAs($other)->post(route('residences.move-out', $character))->assertForbidden();
        $this->actingAs($user)->post(route('residences.move-out', $character))->assertRedirect();
        $this->assertNull($character->fresh()->residence_id);
    }

    #[Test]
    public function une_habitation_fermee_passe_par_l_equipe_et_l_archivage_libere_la_place(): void
    {
        $closed = Residence::query()->create(['name' => 'Villa du lac', 'kind' => 'maison', 'capacity' => 2, 'is_open' => false, 'is_published' => true]);
        [$user, $character] = $this->playerWithCharacter('Hana', 'Mori');

        $this->actingAs($user)->post(route('residences.move-in', $closed), ['character_id' => $character->id])->assertSessionHasErrors('character_id');

        $closed->update(['is_open' => true]);
        $this->actingAs($user)->post(route('residences.move-in', $closed), ['character_id' => $character->id])->assertSessionHasNoErrors();

        app(CharacterWorkflow::class)->archive($character->fresh(), $user);
        $this->assertNull(Character::withTrashed()->find($character->id)->residence_id);
    }

    // Enquêtes et indices -------------------------------------------------------

    #[Test]
    public function un_indice_confie_n_est_lisible_que_par_ses_detenteurs(): void
    {
        [$user, $holder] = $this->playerWithCharacter('Hana', 'Mori');
        [$other] = $this->playerWithCharacter('Ren', 'Abe');

        $investigation = Investigation::query()->create(['title' => 'Les lumières du lac', 'summary' => 'Des lueurs chaque nuit.', 'is_published' => true]);
        $public = Clue::query()->create(['investigation_id' => $investigation->id, 'title' => 'Témoignage du pêcheur', 'body' => 'Il a vu une lanterne.', 'is_public' => true]);
        $secret = Clue::query()->create(['investigation_id' => $investigation->id, 'title' => 'Photographie floue', 'body' => 'Une silhouette sur la rive.', 'is_public' => false]);
        $secret->characters()->attach($holder->id);

        $this->get(route('investigations.show', $investigation))->assertOk()->assertSee('Témoignage du pêcheur')->assertDontSee('Photographie floue');
        $this->get(route('clues.show', [$investigation, $public]))->assertOk()->assertSee('Il a vu une lanterne.');
        $this->get(route('clues.show', [$investigation, $secret]))->assertNotFound();
        $this->actingAs($other)->get(route('clues.show', [$investigation, $secret]))->assertNotFound();
        $this->actingAs($other)->get(route('investigations.show', $investigation))->assertDontSee('Photographie floue');

        $this->actingAs($user)->get(route('investigations.show', $investigation))->assertOk()->assertSee('Photographie floue');
        $this->actingAs($user)->get(route('clues.show', [$investigation, $secret]))->assertOk()->assertSee('Une silhouette sur la rive.')->assertSee('Détenu par Hana Mori');
        $this->actingAs($user)->get(route('clues.mine'))->assertOk()->assertSee('Photographie floue');
        $this->actingAs($other)->get(route('clues.mine'))->assertOk()->assertDontSee('Photographie floue');

        // Une enquête en brouillon ne laisse rien filtrer.
        $investigation->update(['is_published' => false]);
        $this->actingAs($user)->get(route('clues.show', [$investigation, $secret]))->assertNotFound();
        $this->actingAs($user)->get(route('investigations.show', $investigation))->assertNotFound();
    }

    #[Test]
    public function un_detenteur_confie_un_indice_et_le_destinataire_est_prevenu(): void
    {
        [$user, $holder] = $this->playerWithCharacter('Hana', 'Mori');
        [$other, $friend] = $this->playerWithCharacter('Ren', 'Abe');
        [$third, $stranger] = $this->playerWithCharacter('Yuki', 'Sato');

        $investigation = Investigation::query()->create(['title' => 'Les lumières du lac', 'is_published' => true]);
        $secret = Clue::query()->create(['investigation_id' => $investigation->id, 'title' => 'Photographie floue', 'is_public' => false]);
        $locked = Clue::query()->create(['investigation_id' => $investigation->id, 'title' => 'Lettre scellée', 'is_public' => false, 'is_shareable' => false]);
        $secret->characters()->attach($holder->id);
        $locked->characters()->attach($holder->id);

        // Sans détenir l'indice, impossible de le confier.
        $this->actingAs($third)->post(route('clues.share', [$investigation, $secret]), ['from_character_id' => $stranger->id, 'to_character_id' => $stranger->id])->assertForbidden();
        $this->actingAs($user)->post(route('clues.share', [$investigation, $locked]), ['from_character_id' => $holder->id, 'to_character_id' => $friend->id])->assertForbidden();

        $this->actingAs($user)->post(route('clues.share', [$investigation, $secret]), ['from_character_id' => $holder->id, 'to_character_id' => $friend->id])->assertRedirect();

        $this->assertTrue($secret->characters()->whereKey($friend->id)->exists());
        $this->assertSame($holder->id, (int) $secret->characters()->whereKey($friend->id)->first()->pivot->shared_by_character_id);
        $this->assertSame(1, UserNotification::query()->where('user_id', $other->id)->where('type', 'indice')->count());
        $this->actingAs($other)->get(route('clues.show', [$investigation, $secret]))->assertOk();
        $this->actingAs($third)->get(route('clues.show', [$investigation, $secret]))->assertNotFound();
    }

    #[Test]
    public function l_equipe_confie_un_indice_depuis_l_administration(): void
    {
        [$user, $holder] = $this->playerWithCharacter('Hana', 'Mori');
        $investigation = Investigation::query()->create(['title' => 'Les lumières du lac', 'is_published' => true]);

        $this->actingAs($this->staff());

        Livewire::test(CreateClue::class)
            ->fillForm([
                'investigation_id' => $investigation->id,
                'title' => 'Carnet de bord',
                'is_public' => false,
                'is_shareable' => true,
                'characters' => [$holder->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $clue = Clue::query()->where('title', 'Carnet de bord')->firstOrFail();
        $this->assertTrue($clue->characters()->whereKey($holder->id)->exists());
        $this->assertSame(1, UserNotification::query()->where('user_id', $user->id)->where('type', 'indice')->count());
    }

    #[Test]
    public function un_joueur_ne_gere_pas_les_enquetes_dans_l_administration(): void
    {
        $this->actingAs($this->player())->get('/admin/indices')->assertForbidden();
        $this->actingAs($this->staff('moderateur'))->get('/admin/indices')->assertForbidden();
        $this->actingAs($this->staff())->get('/admin/indices')->assertOk();
        $this->actingAs($this->staff())->get('/admin/archives-municipales')->assertOk();
        $this->actingAs($this->staff())->get('/admin/habitations')->assertOk();
        $this->actingAs($this->staff())->get('/admin/enquetes')->assertOk();
        $this->actingAs($this->staff())->get('/admin/annonces')->assertOk();
    }

    // Données personnelles -------------------------------------------------------

    #[Test]
    public function l_export_contient_journal_et_annonces_et_la_suppression_retire_les_annonces(): void
    {
        [$user, $character] = $this->playerWithCharacter('Hana', 'Mori');
        $this->actingAs($user)->post(route('journal.store', $character), ['title' => 'Le lac', 'body' => 'Une page.', 'visibility' => 'prive']);
        $this->actingAs($user)->post(route('ads.store'), ['character_id' => $character->id, 'category' => 'vente', 'title' => 'Vends vélo', 'body' => 'Rouge.', 'duration' => 7]);

        $export = app(AccountData::class)->export($user);
        $this->assertSame('Le lac', $export['journal'][0]['titre']);
        $this->assertSame('Vends vélo', $export['annonces'][0]['titre']);

        app(AccountData::class)->delete($user, false);

        $this->assertSame(0, ClassifiedAd::withTrashed()->count());
        $this->assertSame(0, JournalEntry::withTrashed()->count());
    }
}
