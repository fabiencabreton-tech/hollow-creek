<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\UserBlock;
use App\Models\UserNotification;
use App\Notifications\DailyDigest;
use App\Notifications\SiteNotificationMail;
use App\Services\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\MakesCharacters;
use Tests\TestCase;

class CommunicationTest extends TestCase
{
    use MakesCharacters, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    #[Test]
    public function une_notification_s_affiche_sous_la_lanterne_et_s_ouvre(): void
    {
        $user = $this->player();
        $notification = app(Notifier::class)->send($user, 'fiche', 'Fiche acceptée : Ada Ashby', 'https://exemple.test/mes-personnages');

        $this->assertSame('/mes-personnages', $notification->url, 'Seule l’adresse relative au site est gardée.');
        Notification::assertSentTo($user, SiteNotificationMail::class);

        $this->actingAs($user)->get(route('dashboard'))->assertSee('data-counter="notifications"', false);
        $this->actingAs($user)->getJson(route('notifications.counts'))->assertJson(['notifications' => 1, 'messages' => 0]);
        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('Fiche acceptée : Ada Ashby');

        $this->actingAs($this->player())->get(route('notifications.open', $notification))->assertNotFound();

        $this->actingAs($user)->get(route('notifications.open', $notification))->assertRedirect('/mes-personnages');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[Test]
    public function le_resume_quotidien_regroupe_les_notifications(): void
    {
        $user = $this->player();
        $preferences = ['fiche' => 'quotidien', 'message' => 'aucun'];
        $user->forceFill(['notification_preferences' => $preferences])->save();

        app(Notifier::class)->send($user, 'fiche', 'Première', '/a');
        app(Notifier::class)->send($user, 'fiche', 'Seconde', '/b');
        app(Notifier::class)->send($user, 'message', 'Sans e-mail', '/c');

        Notification::assertNothingSentTo($user);

        $this->assertSame(1, app(Notifier::class)->sendDigests());
        Notification::assertSentTo($user, DailyDigest::class, fn (DailyDigest $digest): bool => $digest->total === 2);

        $this->assertSame(0, UserNotification::query()->whereNull('mailed_at')->count());
        $this->assertSame(0, app(Notifier::class)->sendDigests());
    }

    #[Test]
    public function deux_joueurs_echangent_des_messages(): void
    {
        $alice = $this->player(['pseudo' => 'Alice']);
        $bruno = $this->player(['pseudo' => 'Bruno']);

        $this->actingAs($alice)->post(route('messages.store'), ['recipients' => 'bruno', 'body' => 'On prépare la scène du lac ?'])->assertRedirect();
        $conversation = Conversation::query()->firstOrFail();

        $this->actingAs($bruno)->getJson(route('notifications.counts'))->assertJson(['messages' => 1]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $bruno->id, 'type' => 'message']);

        $this->actingAs($bruno)->get(route('messages.show', $conversation))->assertOk()->assertSee('On prépare la scène du lac ?');
        $this->actingAs($bruno)->getJson(route('notifications.counts'))->assertJson(['messages' => 0]);

        $this->actingAs($this->player())->get(route('messages.show', $conversation))->assertNotFound();
        $this->actingAs($this->player())->getJson(route('messages.poll', $conversation))->assertNotFound();

        $lastId = Message::query()->max('id');
        $this->actingAs($bruno)->post(route('messages.send', $conversation), ['body' => 'Avec plaisir !'])->assertRedirect();
        $this->actingAs($alice)->getJson(route('messages.poll', ['conversation' => $conversation, 'apres' => $lastId]))
            ->assertOk()
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Avec plaisir !'));

        // Une seconde demande à deux reprend la même conversation.
        $this->actingAs($alice)->post(route('messages.store'), ['recipients' => 'Bruno', 'body' => 'Encore moi'])->assertRedirect();
        $this->assertSame(1, Conversation::query()->count());

        $this->actingAs($alice)->get(route('messages.index', ['q' => 'lac']))->assertSee('Bruno');
        $this->actingAs($alice)->post(route('messages.archive', $conversation))->assertRedirect();
        $this->actingAs($alice)->get(route('messages.index'))->assertDontSee(route('messages.show', $conversation));
        $this->actingAs($alice)->get(route('messages.index', ['archives' => 1]))->assertSee(route('messages.show', $conversation));
    }

    #[Test]
    public function un_blocage_ferme_la_messagerie_entre_deux_comptes(): void
    {
        $alice = $this->player(['pseudo' => 'Alice']);
        $bruno = $this->player(['pseudo' => 'Bruno']);
        $this->actingAs($alice)->post(route('messages.store'), ['recipients' => 'Bruno', 'body' => 'Bonjour']);
        $conversation = Conversation::query()->firstOrFail();

        $this->actingAs($bruno)->post(route('blocks.store'), ['pseudo' => 'Alice'])->assertRedirect();
        $this->assertTrue(UserBlock::between($alice, $bruno));

        $this->actingAs($alice)->post(route('messages.send', $conversation), ['body' => 'Tu es là ?'])->assertForbidden();
        $this->actingAs($alice)->post(route('messages.store'), ['recipients' => 'Bruno', 'subject' => 'Nouveau', 'body' => 'Re'])->assertSessionHasErrors('recipients');

        $block = UserBlock::query()->firstOrFail();
        $this->actingAs($alice)->delete(route('blocks.destroy', $block))->assertNotFound();
        $this->actingAs($bruno)->delete(route('blocks.destroy', $block))->assertRedirect();
        $this->assertFalse(UserBlock::between($alice, $bruno));
    }

    #[Test]
    public function l_equipe_ne_peut_pas_etre_bloquee(): void
    {
        $staff = $this->staff('moderateur');

        $this->actingAs($this->player())->post(route('blocks.store'), ['pseudo' => $staff->pseudo])->assertSessionHasErrors('pseudo');
    }

    #[Test]
    public function un_groupe_se_quitte(): void
    {
        $alice = $this->player(['pseudo' => 'Alice']);
        $this->player(['pseudo' => 'Bruno']);
        $carla = $this->player(['pseudo' => 'Carla']);

        $this->actingAs($alice)->post(route('messages.store'), ['recipients' => 'Bruno, Carla', 'subject' => 'Préparer la Toussaint', 'body' => 'Qui en est ?'])->assertRedirect();
        $conversation = Conversation::query()->firstOrFail();

        $this->actingAs($carla)->post(route('messages.leave', $conversation))->assertRedirect();
        $this->actingAs($carla)->get(route('messages.show', $conversation))->assertNotFound();
        $this->actingAs($alice)->get(route('messages.show', $conversation))->assertOk()->assertSee('Préparer la Toussaint');
    }
}
