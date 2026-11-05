<?php

namespace Tests\Feature;

use App\Enums\GazetteRubric;
use App\Models\GazetteArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GazetteTest extends TestCase
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
    public function la_gazette_vide_annonce_son_premier_numero(): void
    {
        $this->get('/gazette')
            ->assertOk()
            ->assertSee('La Gazette de Tsukimori')
            ->assertSee('Le premier numéro de la Gazette est en préparation.');
    }

    #[Test]
    public function un_article_publie_est_lisible_et_date(): void
    {
        $article = $this->article();

        $this->assertNotNull($article->published_at);

        $this->get('/gazette')->assertOk()->assertSee('Brouillard sur le lac Tsukikage');
        $this->get('/gazette/'.$article->slug)->assertOk()->assertSee('Texte rédigé par l’équipe.');
        $this->get('/')->assertOk()->assertSee('Brouillard sur le lac Tsukikage');
    }

    #[Test]
    public function un_brouillon_ou_un_article_programme_reste_cache(): void
    {
        $draft = $this->article(['title' => 'Article en brouillon', 'status' => GazetteArticle::STATUS_DRAFT]);
        $scheduled = $this->article(['title' => 'Article programmé', 'published_at' => now()->addDay()]);

        $this->get('/gazette')->assertDontSee('Article en brouillon')->assertDontSee('Article programmé');
        $this->get('/gazette/'.$draft->slug)->assertNotFound();
        $this->get('/gazette/'.$scheduled->slug)->assertNotFound();

        $this->actingAs($this->staff('administrateur'))
            ->get('/gazette/'.$draft->slug)
            ->assertOk()
            ->assertSee('Aperçu de l’équipe');
    }

    #[Test]
    public function les_rubriques_filtrent_les_articles(): void
    {
        $this->article(['title' => 'Un fait divers', 'rubric' => GazetteRubric::FaitsDivers]);
        $this->article(['title' => 'Une annonce', 'rubric' => GazetteRubric::Annonces]);

        $this->get('/gazette?rubrique=annonces')
            ->assertOk()
            ->assertSee('Une annonce')
            ->assertDontSee('Un fait divers');
    }

    #[Test]
    public function le_texte_des_articles_est_neutralise(): void
    {
        $article = $this->article(['body' => '<script>alert(1)</script> et [lien](javascript:alert(1))']);

        $this->get('/gazette/'.$article->slug)
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('href="javascript:', false);
    }
}
