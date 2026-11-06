<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Notifier;
use App\Support\WebPush\WebPushCrypto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://fcm.googleapis.com/fcm/send/appareil-de-test';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $keys = WebPushCrypto::createVapidKeys();
        config([
            'hollow.push.public_key' => $keys['public'],
            'hollow.push.private_key' => $keys['private'],
            'hollow.push.subject' => 'mailto:equipe@tsukimori.test',
        ]);
    }

    /**
     * Clés d'abonnement comme les fournit un navigateur.
     *
     * @return array{endpoint: string, keys: array{p256dh: string, auth: string}}
     */
    private function browserSubscription(string $endpoint = self::ENDPOINT): array
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $ec = openssl_pkey_get_details($key)['ec'];
        $point = "\x04".str_pad($ec['x'], 32, "\x00", STR_PAD_LEFT).str_pad($ec['y'], 32, "\x00", STR_PAD_LEFT);

        return [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => WebPushCrypto::base64UrlEncode($point), 'auth' => WebPushCrypto::base64UrlEncode(random_bytes(16))],
        ];
    }

    private function subscribe(User $user, ?array $subscription = null): PushSubscription
    {
        $subscription ??= $this->browserSubscription();

        return PushSubscription::query()->forceCreate([
            'user_id' => $user->id,
            'endpoint_hash' => PushSubscription::hashEndpoint($subscription['endpoint']),
            'endpoint' => $subscription['endpoint'],
            'public_key' => $subscription['keys']['p256dh'],
            'auth_token' => $subscription['keys']['auth'],
        ]);
    }

    #[Test]
    public function un_appareil_s_abonne_et_recoit_une_alerte_chiffree_de_bienvenue(): void
    {
        Http::fake(['fcm.googleapis.com/*' => Http::response('', 201)]);
        $player = $this->player();

        $this->actingAs($player)
            ->postJson(route('push.store'), $this->browserSubscription())
            ->assertOk()
            ->assertJson(['ok' => true, 'delivered' => true]);

        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $player->id, 'endpoint_hash' => hash('sha256', self::ENDPOINT)]);

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->url() === self::ENDPOINT
                && $request->hasHeader('Content-Encoding', 'aes128gcm')
                && str_starts_with($request->header('Authorization')[0], 'vapid t=')
                && ! str_contains($request->body(), 'Notifications activées');
        });

        // Remise à jour silencieuse : pas de seconde alerte.
        $this->actingAs($player)->postJson(route('push.store', ['silencieux' => 1]), $this->browserSubscription())->assertOk();
        Http::assertSentCount(1);
    }

    #[Test]
    public function seuls_les_services_push_des_navigateurs_sont_acceptes(): void
    {
        Http::fake();
        $player = $this->player();

        foreach (['https://exemple.test/vol', 'http://fcm.googleapis.com/x', 'https://fcm.googleapis.com:8443/x', 'https://127.0.0.1/x'] as $endpoint) {
            $this->actingAs($player)->postJson(route('push.store'), $this->browserSubscription($endpoint))->assertStatus(422);
        }

        $this->assertDatabaseCount('push_subscriptions', 0);
        Http::assertNothingSent();
    }

    #[Test]
    public function sans_cles_le_site_refuse_poliment(): void
    {
        config(['hollow.push.public_key' => null]);

        $this->actingAs($this->player())->postJson(route('push.store'), $this->browserSubscription())->assertStatus(503);
        $this->actingAs($this->player())->get(route('account.edit'))->assertOk()->assertSee('pas encore activées sur le site');
    }

    #[Test]
    public function une_notification_du_site_part_sur_les_appareils_abonnes(): void
    {
        Http::fake(['fcm.googleapis.com/*' => Http::response('', 201)]);
        $player = $this->player();
        $device = $this->subscribe($player);

        app(Notifier::class)->send($player, 'message', 'Bram vous a écrit', '/messages/3', 'Texte privé du message');

        Http::assertSentCount(1);
        Http::assertSent(fn (HttpRequest $request): bool => ! str_contains($request->body(), 'Texte privé') && ! str_contains($request->body(), 'Bram'));
        $this->assertNotNull($device->fresh()->last_used_at);
    }

    #[Test]
    public function un_type_decoche_ne_part_pas_sur_les_appareils(): void
    {
        Http::fake();
        $player = $this->player();
        $this->subscribe($player);

        $types = array_keys(config('hollow.notifications.types'));
        $this->actingAs($player)->put(route('account.preferences'), [
            'notifications' => array_fill_keys($types, 'aucun'),
            'push' => array_fill_keys(array_diff($types, ['murmure']), '1'),
        ])->assertRedirect();

        $player->refresh();
        $this->assertFalse($player->wantsPush('murmure'));
        $this->assertTrue($player->wantsPush('message'));

        app(Notifier::class)->send($player, 'murmure', 'Un commentaire', '/murmures/1');
        Http::assertNothingSent();
    }

    #[Test]
    public function un_abonnement_expire_est_oublie(): void
    {
        Http::fake(['fcm.googleapis.com/*' => Http::response('', 410)]);
        $player = $this->player();
        $device = $this->subscribe($player);

        app(Notifier::class)->send($player, 'fiche', 'Votre fiche est acceptée', '/mes-personnages');

        $this->assertModelMissing($device);
    }

    #[Test]
    public function chacun_ne_gere_que_ses_appareils(): void
    {
        Http::fake(['fcm.googleapis.com/*' => Http::response('', 201)]);
        $alice = $this->player();
        $bruno = $this->player();
        $device = $this->subscribe($alice);

        $this->actingAs($bruno)->delete(route('push.forget', $device))->assertNotFound();
        $this->actingAs($bruno)->deleteJson(route('push.destroy'), ['endpoint' => self::ENDPOINT])->assertOk();
        $this->assertModelExists($device);

        $this->actingAs($alice)->get(route('account.edit'))->assertSee('Appareils abonnés')->assertSee('Envoyer une notification d’essai');
        $this->actingAs($alice)->post(route('push.test'))->assertRedirect();
        Http::assertSentCount(1);

        $this->actingAs($alice)->delete(route('push.forget', $device))->assertRedirect();
        $this->assertModelMissing($device);

        $this->post(route('push.store'), $this->browserSubscription())->assertRedirect(route('login'));
    }

    #[Test]
    public function la_commande_cree_des_cles_valides(): void
    {
        $this->artisan('hollow:cles-push', ['--afficher' => true])
            ->expectsOutputToContain('WEBPUSH_PUBLIC_KEY=')
            ->expectsOutputToContain('WEBPUSH_PRIVATE_KEY=')
            ->assertSuccessful();

        $keys = WebPushCrypto::createVapidKeys();
        $this->assertSame(65, strlen(WebPushCrypto::base64UrlDecode($keys['public'])));
        $this->assertSame(32, strlen(WebPushCrypto::base64UrlDecode($keys['private'])));
    }
}
