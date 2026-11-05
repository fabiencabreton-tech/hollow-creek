<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Family;
use App\Models\Location;
use App\Models\Species;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UniverseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_page_univers_presente_le_contexte_et_les_rubriques(): void
    {
        $this->get('/univers')
            ->assertOk()
            ->assertSee('Quarante-trois personnes périrent dans les flammes.')
            ->assertSee('Nuit des Lanternes')
            ->assertSee('Les espèces')
            ->assertSee('Les familles fondatrices');
    }

    #[Test]
    public function les_six_especes_sont_publiees(): void
    {
        $response = $this->get('/univers/especes')->assertOk();

        foreach (['Humains', 'Revenants', 'Fantômes', 'Médiums', 'Sorcières et sorciers', 'Chasseurs de spectres'] as $name) {
            $response->assertSee($name);
        }

        $this->get('/univers/especes/revenants')
            ->assertOk()
            ->assertSee('revenues physiquement en 2026');
    }

    #[Test]
    public function les_rubriques_vides_d_une_espece_ne_s_affichent_pas(): void
    {
        $this->get('/univers/especes/humains')->assertOk()->assertDontSee('Capacités');

        Species::query()->where('slug', 'humains')->update(['abilities' => 'Une capacité **écrite par l’équipe**.']);

        $this->get('/univers/especes/humains')
            ->assertOk()
            ->assertSee('Capacités')
            ->assertSee('<strong>écrite par l’équipe</strong>', false);
    }

    #[Test]
    public function les_familles_fondatrices_sont_publiees(): void
    {
        $this->get('/univers/familles')
            ->assertOk()
            ->assertSee('Les Whitmore')
            ->assertSee('Les Blackwell')
            ->assertSee('Les Ashford')
            ->assertSee('Les Holloway');

        $family = Family::query()->where('name', 'Les Holloway')->firstOrFail();

        $this->get('/univers/familles/'.$family->slug)
            ->assertOk()
            ->assertSee('dirigeait l’enquête');
    }

    #[Test]
    public function la_ville_presente_quartiers_et_lieux(): void
    {
        $this->get('/ville')
            ->assertOk()
            ->assertSee('Old Creek District')
            ->assertSee('The Willow Café')
            ->assertSee('Blackwood Books')
            ->assertSee('Hollow Lake');

        $district = District::query()->where('name', 'Old Creek District')->firstOrFail();
        $this->get('/ville/quartiers/'.$district->slug)->assertOk()->assertSee('demeures victoriennes');

        $location = Location::query()->where('name', 'Hollow Lake')->firstOrFail();
        $this->get('/ville/lieux/'.$location->slug)->assertOk()->assertSee('la Nuit des Lanternes y est organisée');
    }

    #[Test]
    public function un_brouillon_est_introuvable_pour_les_visiteurs_mais_visible_par_l_equipe(): void
    {
        $draft = Location::query()->where('name', 'Mairie')->firstOrFail();
        $this->assertFalse($draft->is_published);

        $this->get('/ville')->assertDontSee('Mairie');
        $this->get('/ville/lieux/'.$draft->slug)->assertNotFound();
        $this->actingAs($this->player())->get('/ville/lieux/'.$draft->slug)->assertNotFound();

        $this->actingAs($this->staff('administrateur'))
            ->get('/ville/lieux/'.$draft->slug)
            ->assertOk()
            ->assertSee('Aperçu de l’équipe');
    }

    #[Test]
    public function le_secret_de_l_intrigue_n_apparait_nulle_part(): void
    {
        $urls = ['/', '/univers', '/univers/especes', '/univers/familles', '/ville', '/gazette', '/rejoindre', '/faq'];

        foreach (Location::query()->published()->get() as $location) {
            $urls[] = '/ville/lieux/'.$location->slug;
        }

        foreach ($urls as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('Hargrove')
                ->assertDontSee('sceau magique')
                ->assertDontSee('espace intermédiaire');
        }
    }

    #[Test]
    public function le_contenu_de_depart_n_est_importe_qu_une_fois(): void
    {
        $this->assertSame(6, Species::query()->count());

        Species::query()->where('slug', 'humains')->delete();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(5, Species::query()->count());
        $this->assertSame(4, Family::query()->count());
    }

    #[Test]
    public function une_adresse_unique_est_creee_a_partir_du_nom(): void
    {
        $first = Species::query()->create(['name' => 'Gardiens du Lac', 'treatment' => 'humain']);
        $second = Species::query()->create(['name' => 'Gardiens du lac', 'treatment' => 'humain']);

        $this->assertSame('gardiens-du-lac', $first->slug);
        $this->assertSame('gardiens-du-lac-2', $second->slug);
    }
}
