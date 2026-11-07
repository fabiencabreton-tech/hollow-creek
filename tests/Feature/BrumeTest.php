<?php

namespace Tests\Feature;

use App\Enums\GazetteRubric;
use App\Models\GazetteArticle;
use App\Models\Location;
use App\Models\Species;
use App\Support\Settings;
use Database\Seeders\ContentUpdatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Refonte « Brume » : navigation de la maquette, illustrations, Gazette (rubriques,
 * recherche, archives par année, dossier spécial).
 */
class BrumeTest extends TestCase
{
    use RefreshDatabase;

    protected function article(array $attributes = []): GazetteArticle
    {
        return GazetteArticle::query()->create(array_merge([
            'title' => 'Brouillard sur le lac Tsukikage',
            'rubric' => GazetteRubric::VieLocale,
            'byline' => 'La rédaction',
            'body' => 'Texte rédigé par l’équipe.',
            'status' => GazetteArticle::STATUS_PUBLISHED,
        ], $attributes));
    }

    #[Test]
    public function la_navigation_suit_la_maquette(): void
    {
        $response = $this->get('/univers')->assertOk();

        foreach (['Accueil', 'Contexte', 'Maisons', 'Espèces', 'Gazette', 'Carte', 'RPG', 'Scènes', 'Personnages', 'Rôles prédéfinis', 'Murmures', 'Événements', 'Enquêtes'] as $label) {
            $response->assertSee($label);
        }

        $response->assertSee('Tsukimori Machi')->assertSee('月森町', false);
        // Le bandeau illustré de la section.
        $response->assertSee('page-hero--foret-brume', false);
    }

    #[Test]
    public function le_credit_des_illustrations_se_regle_dans_l_administration(): void
    {
        $this->get('/')->assertSee('Illustrations d’ambiance : images générées');

        app(Settings::class)->set('illustrations_credit', 'Illustrations : l’équipe de Tsukimori');
        $this->get('/')->assertSee('Illustrations : l’équipe de Tsukimori')->assertDontSee('images générées');

        app(Settings::class)->set('illustrations_credit', '');
        $this->get('/')->assertDontSee('site-footer__credit', false);
    }

    #[Test]
    public function l_accueil_affiche_la_une_et_les_derniers_articles(): void
    {
        $this->article(['title' => 'Article à la une', 'is_featured' => true]);
        $this->article(['title' => 'Un fait divers', 'rubric' => GazetteRubric::FaitsDivers]);

        $this->get('/')
            ->assertOk()
            ->assertSee('À la une')
            ->assertSee('Article à la une')
            ->assertSee('Derniers articles')
            ->assertSee('Un fait divers');
    }

    #[Test]
    public function la_gazette_cherche_classe_par_annee_et_met_un_dossier_en_avant(): void
    {
        $this->article(['title' => 'La nuit de l’incendie', 'rp_date' => 'Vendredi 1er novembre 1996', 'body' => 'Les pompiers de Nagano.']);
        $this->article(['title' => 'Le marché du dimanche', 'rp_date' => 'Dimanche 8 novembre 2026', 'dossier' => 'Les lanternes du lac']);
        $this->article(['title' => 'Une rumeur au port', 'rubric' => GazetteRubric::Rumeurs, 'rp_date' => 'Lundi 9 novembre 2026', 'dossier' => 'Les lanternes du lac']);

        $index = $this->get('/gazette')->assertOk();
        $index->assertSee('Dossier spécial')->assertSee('Les lanternes du lac')->assertSee('2 articles');
        $index->assertSee('1996')->assertSee('2026')->assertSee('Rumeurs et témoignages')->assertSee('Actualités locales');

        $this->get('/gazette?q=pompiers')->assertOk()->assertSee('La nuit de l’incendie')->assertDontSee('Le marché du dimanche');
        $this->get('/gazette?annee=1996')->assertOk()->assertSee('La nuit de l’incendie')->assertDontSee('Une rumeur au port');
        $this->get('/gazette?dossier=Les+lanternes+du+lac')->assertOk()->assertSee('Une rumeur au port')->assertDontSee('La nuit de l’incendie');
        $this->get('/gazette?rubrique=rumeurs-et-temoignages')->assertOk()->assertSee('Une rumeur au port')->assertDontSee('Le marché du dimanche');

        // Les caractères spéciaux de la recherche ne sont pas des jokers.
        $this->get('/gazette?q=%25')->assertOk()->assertSee('Aucun article ne correspond pour le moment.');
    }

    #[Test]
    public function un_article_propose_son_dossier_et_d_autres_lectures(): void
    {
        $first = $this->article(['title' => 'Premier volet', 'dossier' => 'Les lanternes du lac']);
        $this->article(['title' => 'Second volet', 'dossier' => 'Les lanternes du lac']);
        $this->article(['title' => 'Autre nouvelle']);
        $this->article(['title' => 'Brouillon caché', 'status' => GazetteArticle::STATUS_DRAFT, 'dossier' => 'Les lanternes du lac']);

        $this->get(route('gazette.show', $first))
            ->assertOk()
            ->assertSee('Dans le même dossier')
            ->assertSee('Second volet')
            ->assertSee('À lire aussi')
            ->assertSee('Autre nouvelle')
            ->assertDontSee('Brouillon caché');
    }

    #[Test]
    public function les_especes_a_nom_japonais_recoivent_leur_ecriture(): void
    {
        $this->seed(ContentUpdatesSeeder::class);

        $this->assertSame('幽霊', Species::query()->where('slug', 'yurei')->value('japanese_name'));
        $this->assertSame('怨霊', Species::query()->where('slug', 'onryo')->value('japanese_name'));
        $this->assertNull(Species::query()->where('slug', 'humains')->value('japanese_name'));

        $this->get('/univers/especes')->assertOk()->assertSee('幽霊', false);
    }

    #[Test]
    public function une_fiche_sans_image_montre_une_illustration_d_ambiance(): void
    {
        $this->get('/univers/especes/yurei')
            ->assertOk()
            ->assertSee('figure--ambiance', false)
            ->assertSee('sanctuaire-800', false)
            ->assertDontSee('Illustration à venir');

        $ruins = Location::query()->where('name', 'Ancien hôpital psychiatrique de Kirisawa')->firstOrFail();
        $this->get(route('locations.show', $ruins))->assertOk()->assertSee('kirisawa-800', false);

        $lake = Location::query()->where('name', 'Lac Tsukikage')->firstOrFail();
        $this->get(route('locations.show', $lake))->assertOk()->assertSee('lac-torii-960', false);
    }
}
