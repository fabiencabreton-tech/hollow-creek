<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function validData(array $overrides = []): array
    {
        return array_merge([
            'pseudo' => 'Élise',
            'email' => 'Elise@Example.org',
            'password' => 'une phrase de passe solide',
            'password_confirmation' => 'une phrase de passe solide',
            'age' => '1',
            'terms' => '1',
        ], $overrides);
    }

    #[Test]
    public function inscriptions_fermees_aucun_compte_n_est_cree(): void
    {
        $this->openRegistrations(false);

        $this->get('/inscription')->assertOk()->assertSee('Les inscriptions sont fermées');

        $this->post('/inscription', $this->validData())->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    #[Test]
    public function un_joueur_peut_s_inscrire_et_recoit_un_lien_de_verification(): void
    {
        Notification::fake();
        $this->openRegistrations();

        $this->post('/inscription', $this->validData())->assertRedirect('/tableau-de-bord');

        $user = User::query()->sole();
        $this->assertSame('Élise', $user->pseudo);
        $this->assertSame('elise', $user->pseudo_key);
        $this->assertSame('elise@example.org', $user->email);
        $this->assertNotNull($user->age_confirmed_at);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertTrue($user->hasRole('joueur'));
        $this->assertFalse($user->isStaff());
        $this->assertAuthenticatedAs($user);

        Notification::assertSentTo($user, VerifyEmail::class);

        // Tant que l'adresse n'est pas vérifiée, le tableau de bord reste fermé.
        $this->get('/tableau-de-bord')->assertRedirect('/verifier-adresse-email');
    }

    #[Test]
    public function un_pseudo_trop_proche_d_un_pseudo_existant_est_refuse(): void
    {
        $this->openRegistrations();
        $this->player(['pseudo' => 'Elise']);

        $this->post('/inscription', $this->validData(['pseudo' => 'élise', 'email' => 'autre@example.org']))
            ->assertSessionHasErrors('pseudo');

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function l_adresse_e_mail_est_unique_sans_tenir_compte_des_majuscules(): void
    {
        $this->openRegistrations();
        $this->player(['email' => 'elise@example.org']);

        $this->post('/inscription', $this->validData(['pseudo' => 'Autre']))
            ->assertSessionHasErrors('email');
    }

    #[Test]
    public function l_attestation_d_age_et_les_conditions_sont_obligatoires(): void
    {
        $this->openRegistrations();

        $this->post('/inscription', $this->validData(['age' => null, 'terms' => null]))
            ->assertSessionHasErrors(['age', 'terms']);

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function un_mot_de_passe_trop_court_est_refuse(): void
    {
        $this->openRegistrations();

        $this->post('/inscription', $this->validData(['password' => 'court', 'password_confirmation' => 'court']))
            ->assertSessionHasErrors('password');
    }

    #[Test]
    public function le_champ_piege_bloque_les_robots(): void
    {
        $this->openRegistrations();

        $this->post('/inscription', $this->validData(['site_web' => 'http://spam.example']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function les_inscriptions_sont_limitees_par_adresse_ip(): void
    {
        $this->openRegistrations();

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/inscription', $this->validData(['pseudo' => '', 'email' => "essai{$i}@example.org"]));
        }

        $this->post('/inscription', $this->validData(['email' => 'sixieme@example.org']))->assertStatus(429);
    }
}
