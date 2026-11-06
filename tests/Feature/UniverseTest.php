<?php

namespace Tests\Feature;

use App\Models\District;
use App\Enums\SceneStatus;
use App\Enums\SceneVisibility;
use App\Models\Character;
use App\Models\Family;
use App\Models\KirisawaVictim;
use App\Models\Location;
use App\Models\Scene;
use App\Models\Species;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UniverseSeeder;
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
            ->assertSee('Tsukimori')
            ->assertSee('Quarante-trois personnes décèdent')
            ->assertSee('La quarante-quatrième lanterne')
            ->assertSee('Le bestiaire')
            ->assertSee('Le registre de Kirisawa');
    }

    #[Test]
    public function le_bestiaire_presente_natures_specialisations_et_conditions(): void
    {
        $response = $this->get('/univers/especes')->assertOk();

        foreach (['Natures', 'Humains', 'Yūrei', 'Onryō', 'Revenus', 'Rappelés', 'Médiums', 'Praticiens de l’occulte', 'Maudits'] as $name) {
            $response->assertSee($name);
        }

        $this->get('/univers/especes/onryo')
            ->assertOk()
            ->assertSee('Yūrei')
            ->assertSee('Restrictions');

        $this->get('/univers/especes/revenus')
            ->assertOk()
            ->assertSee('Consulter le registre de Kirisawa');
    }

    #[Test]
    public function les_rubriques_vides_d_une_categorie_ne_s_affichent_pas(): void
    {
        Species::query()->where('slug', 'humains')->update(['abilities' => null]);
        $this->get('/univers/especes/humains')->assertOk()->assertDontSee('id="abilities"', false);

        Species::query()->where('slug', 'humains')->update(['abilities' => 'Une capacité **écrite par l’équipe**.']);

        $this->get('/univers/especes/humains')
            ->assertOk()
            ->assertSee('id="abilities"', false)
            ->assertSee('<strong>écrite par l’équipe</strong>', false);
    }

    #[Test]
    public function les_familles_de_hollow_creek_ont_disparu(): void
    {
        $this->assertSame(4, Family::query()->where('is_major', true)->count());
        $this->get('/univers/familles')->assertOk()->assertDontSee('Holloway')->assertSee('Famille Minase');
    }

    #[Test]
    public function la_ville_presente_quartiers_lieux_et_carte(): void
    {
        $response = $this->get('/ville')->assertOk();

        foreach (['Honmachi', 'Furumachi', 'Kohan', 'Kitayama', 'Minamimachi', 'Lac Tsukikage', 'Kissa Komorebi', 'Temple Jōren', 'La carte de Tsukimori'] as $name) {
            $response->assertSee($name);
        }

        $location = Location::query()->where('name', 'Kissa Komorebi')->firstOrFail();
        $response
            ->assertSee('data-place="'.$location->slug.'"', false)
            ->assertSee('data-map-data', false);

        $this->get('/ville/lieux/'.$location->slug)->assertOk()->assertSee('Café.');
    }

    #[Test]
    public function la_carte_n_expose_que_les_scenes_publiques_en_cours(): void
    {
        $location = Location::query()->where('name', 'Pont Yūgure')->firstOrFail();
        $creator = $this->player();

        Scene::query()->forceCreate(['title' => 'Brume sur le pont', 'location_id' => $location->id, 'user_id' => $creator->id, 'visibility' => SceneVisibility::Public, 'status' => SceneStatus::Ongoing]);
        Scene::query()->forceCreate(['title' => 'Rendez-vous secret', 'location_id' => $location->id, 'user_id' => $creator->id, 'visibility' => SceneVisibility::Private, 'status' => SceneStatus::Ongoing]);

        $this->get('/ville')
            ->assertOk()
            ->assertSee('Brume sur le pont')
            ->assertDontSee('Rendez-vous secret')
            ->assertSee('scenes\\/nouvelle?lieu='.$location->slug, false);
    }

    #[Test]
    public function un_brouillon_est_introuvable_pour_les_visiteurs_mais_visible_par_l_equipe(): void
    {
        $draft = Location::query()->create(['name' => 'Entrepôt abandonné', 'kind' => 'Lieu', 'is_published' => false, 'map_x' => 50, 'map_y' => 50]);

        $this->get('/ville')->assertDontSee('Entrepôt abandonné');
        $this->get('/ville/lieux/'.$draft->slug)->assertNotFound();
        $this->actingAs($this->player())->get('/ville/lieux/'.$draft->slug)->assertNotFound();

        $this->actingAs($this->staff('administrateur'))
            ->get('/ville/lieux/'.$draft->slug)
            ->assertOk()
            ->assertSee('Aperçu de l’équipe');
    }

    #[Test]
    public function les_secrets_de_l_intrigue_n_apparaissent_nulle_part(): void
    {
        KirisawaVictim::query()->create(['number' => 7, 'first_name' => 'Aki', 'last_name' => 'Mori', 'role' => 'personnel', 'status' => 'ouvert', 'is_published' => true, 'admin_notes' => 'Note confidentielle de l’équipe.']);

        $urls = ['/', '/univers', '/univers/especes', '/univers/especes/rappeles', '/univers/especes/revenus', '/registre-de-kirisawa', '/ville', '/gazette', '/rejoindre', '/faq'];

        foreach (Location::query()->published()->get() as $location) {
            $urls[] = '/ville/lieux/'.$location->slug;
        }

        foreach ($urls as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('lien entre les Rappelés')
                ->assertDontSee('Note confidentielle');
        }
    }

    #[Test]
    public function le_registre_ne_montre_que_les_victimes_publiees(): void
    {
        KirisawaVictim::query()->create(['number' => 3, 'first_name' => 'Haru', 'last_name' => 'Sato', 'role' => 'patient', 'status' => 'ouvert', 'is_published' => true]);
        KirisawaVictim::query()->create(['number' => 4, 'first_name' => 'Rin', 'last_name' => 'Kudo', 'role' => 'patient', 'status' => 'administratif', 'is_published' => false]);

        $this->get('/registre-de-kirisawa')
            ->assertOk()
            ->assertSee('Haru Sato')
            ->assertDontSee('Rin Kudo')
            ->assertSee('1 victime peut actuellement être incarnée.');
    }

    #[Test]
    public function le_contenu_de_depart_n_est_importe_qu_une_fois(): void
    {
        $this->assertSame(8, Species::query()->count());
        $this->assertSame(5, District::query()->count());

        Species::query()->where('slug', 'maudits')->delete();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(7, Species::query()->count());
    }

    #[Test]
    public function une_base_hollow_creek_passe_a_tsukimori_sans_perdre_ses_personnages(): void
    {
        // État d'une base créée avant Tsukimori.
        Location::query()->delete();
        District::query()->delete();
        Species::query()->delete();

        $old = fn (string $slug, string $name, string $treatment): Species => tap(new Species, fn (Species $species) => $species->forceFill([
            'slug' => $slug, 'name' => $name, 'treatment' => $treatment, 'kind' => 'nature', 'is_published' => true,
        ])->saveQuietly());

        $humans = $old('humains', 'Humains', 'humain');
        $revenants = $old('revenants', 'Revenants', 'revenu');
        $mediums = $old('mediums', 'Médiums', 'medium');
        $hunters = $old('chasseurs-de-spectres', 'Chasseurs de spectres', 'humain');
        $family = Family::query()->create(['name' => 'Les Holloway', 'is_published' => true]);
        District::query()->create(['name' => 'Old Creek District', 'is_published' => true]);
        Location::query()->create(['name' => 'Hollow Lake', 'is_published' => true]);

        $owner = $this->player();
        $make = function (string $first, Species $species) use ($owner, $family): Character {
            $character = new Character(['first_name' => $first, 'last_name' => 'Test', 'age' => 30, 'profession' => 'Libraire']);
            $character->forceFill(['user_id' => $owner->id, 'species_id' => $species->id, 'family_id' => $family->id])->save();

            return $character;
        };
        $ghost = $make('Ada', $revenants);
        $seer = $make('Bea', $mediums);
        $hunter = $make('Cy', $hunters);

        app(Settings::class)->set(UniverseSeeder::DONE_KEY, false);
        $this->seed(UniverseSeeder::class);

        $this->assertSame('revenus', $ghost->fresh()->species->slug);
        $this->assertSame('Revenus', $ghost->fresh()->species->name);
        $this->assertSame('humains', $seer->fresh()->species->slug);
        $this->assertTrue($seer->fresh()->hasTrait('mediums'));
        $this->assertSame($humans->id, $hunter->fresh()->species_id);
        $this->assertNull($ghost->fresh()->family_id);
        $this->assertFalse(Family::query()->where('name', 'Les Holloway')->exists());
        $this->assertFalse(Location::query()->where('name', 'Hollow Lake')->exists());
        $this->assertFalse(District::query()->where('name', 'Old Creek District')->exists());
        $this->assertSame(8, Species::query()->count());
        $this->assertSame(5, District::query()->count());
        $this->get('/univers/especes')->assertOk()->assertSee('Onryō');
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
