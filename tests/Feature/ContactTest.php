<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Filament\Resources\ContactTickets\Pages\ViewContactTicket;
use App\Models\ContactTicket;
use App\Models\Faceclaim;
use App\Models\FaceclaimClaim;
use App\Models\User;
use App\Notifications\ContactTicketUpdate;
use App\Notifications\GuestClaimConfirmation;
use App\Services\FaceclaimRegistry;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    #[Test]
    public function la_bulle_annonce_si_l_equipe_est_en_ligne(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Écrire à l’équipe')->assertSee('Nous répondons en général sous 24 heures.');

        $staff = $this->staff('moderateur');
        $staff->forceFill(['last_seen_at' => now()])->save();
        cache()->forget('hollow.equipe-en-ligne');

        $this->get(route('home'))->assertSee('Un membre de l’équipe est en ligne.');
    }

    #[Test]
    public function un_visiteur_ecrit_a_l_equipe_et_suit_sa_demande_par_lien_secret(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Morgane',
            'email' => 'Morgane@exemple.test',
            'motif' => 'question',
            'subject' => 'Les revenants peuvent-ils sortir de la ville ?',
            'body' => 'Bonjour, je me pose la question avant de créer ma fiche.',
        ])->assertRedirect(route('contact.sent'));

        $ticket = ContactTicket::query()->firstOrFail();
        $this->assertMatchesRegularExpression('/^HC-[A-Z2-9]{4}$/', $ticket->reference);
        $this->assertSame('morgane@exemple.test', $ticket->email);
        $this->assertNotNull($ticket->token_hash);

        $url = null;
        Notification::assertSentOnDemand(ContactTicketUpdate::class, function (ContactTicketUpdate $notification, array $channels, object $notifiable) use (&$url): bool {
            $url = $notification->trackingUrl;

            return $notifiable->routes['mail'] === 'morgane@exemple.test';
        });

        $path = parse_url($url, PHP_URL_PATH);
        $this->get($path)->assertOk()->assertSee('je me pose la question');
        $this->get($this->tamper($path))->assertOk()->assertSee('Ce lien de suivi ne fonctionne plus');

        $this->post($path, ['body' => 'Et j’ajoute une précision.'])->assertRedirect();
        $this->assertSame(2, $ticket->messages()->count());

        // L'équipe répond : nouveau lien, l'ancien ne fonctionne plus.
        $admin = $this->staff('administrateur');
        Livewire::actingAs($admin)
            ->test(ViewContactTicket::class, ['record' => $ticket->reference])
            ->callAction('note', ['body' => 'Penser à la FAQ.'])
            ->callAction('reply', ['body' => 'Oui, mais pas au-delà du pont couvert.'])
            ->assertHasNoActionErrors();

        $this->assertSame(ContactTicket::STATUS_ANSWERED, $ticket->fresh()->status);
        auth()->guard('web')->logout();
        $this->get($path)->assertSee('Ce lien de suivi ne fonctionne plus');

        $newUrl = null;
        Notification::assertSentOnDemand(ContactTicketUpdate::class, function (ContactTicketUpdate $notification) use (&$newUrl): bool {
            if ($notification->kind === 'answered') {
                $newUrl = $notification->trackingUrl;
            }

            return true;
        });

        $this->get(parse_url($newUrl, PHP_URL_PATH))
            ->assertOk()
            ->assertSee('Oui, mais pas au-delà du pont couvert.')
            ->assertDontSee('Penser à la FAQ.');
    }

    #[Test]
    public function le_lien_de_suivi_se_redemande_sans_reveler_l_existence_de_la_demande(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Morgane', 'email' => 'morgane@exemple.test', 'motif' => 'probleme',
            'subject' => 'Bug', 'body' => 'La page plante.',
        ]);
        $ticket = ContactTicket::query()->firstOrFail();

        $this->post(route('contact.resend.send'), ['reference' => $ticket->reference, 'email' => 'autre@exemple.test'])
            ->assertRedirect(route('contact.resend'))->assertSessionHas('status');
        Notification::assertSentOnDemandTimes(ContactTicketUpdate::class, 1);

        $this->post(route('contact.resend.send'), ['reference' => strtolower($ticket->reference), 'email' => 'MORGANE@exemple.test'])
            ->assertSessionHas('status');
        Notification::assertSentOnDemandTimes(ContactTicketUpdate::class, 2);
    }

    #[Test]
    public function un_joueur_retrouve_ses_demandes_dans_son_espace(): void
    {
        $player = $this->player();

        $this->actingAs($player)->post(route('contact.store'), [
            'motif' => 'predefini', 'subject' => 'Le gardien du phare', 'body' => 'Ce rôle est-il toujours libre ?',
        ])->assertRedirect();

        $ticket = ContactTicket::query()->firstOrFail();
        $this->assertSame($player->id, $ticket->user_id);

        $this->actingAs($player)->get(route('contact.mine'))->assertSee('Le gardien du phare');
        $this->actingAs($player)->get(route('contact.mine.show', $ticket))->assertOk();
        $this->actingAs($this->player())->get(route('contact.mine.show', $ticket))->assertNotFound();

        Livewire::actingAs($this->staff('administrateur'))
            ->test(ViewContactTicket::class, ['record' => $ticket->reference])
            ->callAction('reply', ['body' => 'Oui, il est libre.']);

        $this->assertDatabaseHas('user_notifications', ['user_id' => $player->id, 'type' => 'equipe']);
    }

    #[Test]
    public function le_champ_piege_bloque_les_robots(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Robot', 'email' => 'robot@exemple.test', 'motif' => 'question',
            'subject' => 'Offre', 'body' => 'Achetez', 'site_web' => 'http://spam.test',
        ]);

        $this->assertSame(0, ContactTicket::query()->count());
    }

    #[Test]
    public function une_demande_d_avatar_sans_compte_se_confirme_puis_rejoint_le_compte(): void
    {
        $this->post(route('faceclaims.guest'), [
            'name' => 'Cate Blanchett',
            'guest_pseudo' => 'Morgane',
            'guest_email' => 'morgane@exemple.test',
        ])->assertRedirect(route('faceclaims.index'));

        $claim = FaceclaimClaim::query()->firstOrFail();
        $this->assertSame(ClaimStatus::Unconfirmed, $claim->status);
        $this->assertNull(Faceclaim::query()->firstOrFail()->activeClaim, 'Une demande non confirmée ne bloque pas la célébrité.');

        $url = null;
        Notification::assertSentOnDemand(GuestClaimConfirmation::class, function (GuestClaimConfirmation $notification) use (&$url): bool {
            $url = $notification->url;

            return true;
        });

        $this->get($this->tamper(parse_url($url, PHP_URL_PATH)))->assertSessionHasErrors('claim');
        $this->get(parse_url($url, PHP_URL_PATH))->assertRedirect(route('faceclaims.index'));
        $this->assertSame(ClaimStatus::Pending, $claim->fresh()->status);

        $this->get(route('faceclaims.index', ['q' => 'Cate Blanchett']))->assertSee('Demande en cours');

        $user = User::factory()->create(['email' => 'morgane@exemple.test']);
        event(new Verified($user));
        $this->assertSame($user->id, $claim->fresh()->user_id);

        // Les demandes non confirmées expirent au bout de 48 heures.
        [$other] = app(FaceclaimRegistry::class)->requestAsGuest('Tilda Swinton', 'Sam', 'sam@exemple.test');
        $this->travel(49)->hours();
        app(FaceclaimRegistry::class)->expireOverdue();
        $this->assertSame(ClaimStatus::Expired, $other->fresh()->status);
    }

    #[Test]
    public function seule_l_equipe_habilitee_lit_les_demandes(): void
    {
        $this->actingAs($this->staff('moderateur'))->get('/admin/demandes')->assertOk();
        $this->actingAs($this->player())->get('/admin/demandes')->assertForbidden();
    }

    /**
     * Le même lien avec un dernier caractère différent.
     */
    private function tamper(string $path): string
    {
        return substr($path, 0, -1).(str_ends_with($path, 'x') ? 'y' : 'x');
    }
}
