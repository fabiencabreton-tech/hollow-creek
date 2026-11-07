@php($ambiance = \App\Support\Ambiance::class)
<x-layout title="La Gazette de Tsukimori" description="La Gazette de Tsukimori, le journal local : actualités, faits divers, rumeurs et témoignages, vie locale, annonces et avis de décès.">
    <header class="gazette-banner">
        <x-ambiance name="gazette" eager class="gazette-banner__image" />
        <div class="container gazette-banner__inner">
            <div class="gazette-banner__title">
                <x-picto name="lune" class="gazette-banner__moon" />
                <p class="gazette-banner__eyebrow">La Gazette de Tsukimori</p>
                <h1 class="gazette-banner__kanji">
                    <span lang="ja" aria-hidden="true">月森新聞</span>
                    <span class="visually-hidden">La Gazette de Tsukimori{{ $rubric ? ' — '.$rubric->label() : '' }}</span>
                </h1>
                <x-ornament class="gazette-banner__ornament" />
                <p class="gazette-banner__tagline">Les histoires que la ville n’a pas oubliées.</p>
                <p class="gazette-banner__date">{{ $rpDate }}</p>
            </div>
        </div>
    </header>

    <nav class="rubric-tabs" aria-label="Rubriques">
        <div class="container">
            <ul class="rubric-tabs__list">
                <li><a href="{{ route('gazette.index') }}" @if (! $filtered) aria-current="page" @endif><x-picto name="sakura" />Tous les articles</a></li>
                @foreach ($rubrics as $item)
                    <li><a href="{{ route('gazette.index', ['rubrique' => $item->value]) }}" @if ($rubric === $item) aria-current="page" @endif><x-picto :name="$item->icon()" />{{ $item->label() }}</a></li>
                @endforeach
                <li><a href="{{ route('archives.index') }}"><x-picto name="livre" />Archives</a></li>
            </ul>
        </div>
    </nav>

    <div class="container gazette-layout">
        <div class="gazette-main">
            @if ($filtered)
                <p class="filter-note" role="status">
                    @if ($rubric) Rubrique <strong>{{ $rubric->label() }}</strong> @endif
                    @if ($search !== '') Recherche <strong>« {{ $search }} »</strong> @endif
                    @if ($year) Année <strong>{{ $year }}</strong> @endif
                    @if ($dossier) Dossier <strong>{{ $dossier }}</strong> @endif
                    · {{ $articles->total() }} {{ $articles->total() > 1 ? 'articles' : 'article' }}
                    · <a href="{{ route('gazette.index') }}">Toute la Gazette</a>
                </p>
            @endif

            @if ($featured)
                <article class="une une--paper" aria-labelledby="article-une">
                    <a class="une__photo photo-print photo-print--tape" href="{{ route('gazette.show', $featured) }}" tabindex="-1" aria-hidden="true">
                        <x-cover :model="$featured" :fallback="$ambiance::forArticle($featured)" />
                    </a>
                    <div class="une__body">
                        <p class="une__meta"><span class="tag tag--ink">À la une</span><span>{{ $featured->rubric->label() }}@if ($featured->rp_date) · {{ $featured->rp_date }}@endif</span></p>
                        <h2 id="article-une" class="une__title"><a href="{{ route('gazette.show', $featured) }}">{{ $featured->title }}</a></h2>
                        <p class="byline">Par {{ $featured->byline }}</p>
                        @if ($featured->excerpt)
                            <p class="une__excerpt">{{ $featured->excerpt }}</p>
                        @endif
                        <a class="more-link" href="{{ route('gazette.show', $featured) }}">Lire l’article <x-picto name="fleche" /></a>
                    </div>
                </article>
            @endif

            @if ($articles->isEmpty() && ! $featured)
                <div class="empty-note">
                    @include('partials.stopped-clock')
                    <p>{{ $filtered ? 'Aucun article ne correspond pour le moment.' : 'Le premier numéro de la Gazette est en préparation.' }}</p>
                </div>
            @elseif ($articles->isNotEmpty())
                <h2 class="rule-title"><x-picto name="sakura" />{{ $filtered ? 'Articles' : 'Dernières publications' }}</h2>
                <div class="press-grid">
                    @foreach ($articles as $article)
                        <article class="press-card">
                            <a class="press-card__image" href="{{ route('gazette.show', $article) }}" tabindex="-1" aria-hidden="true">
                                <x-cover :model="$article" :fallback="$ambiance::forArticle($article)" size="small" />
                            </a>
                            <p class="press-card__meta">{{ $article->rubric->label() }}@if ($articleYear = \App\Http\Controllers\GazetteController::yearOf($article)) · {{ $articleYear }}@endif</p>
                            <h3 class="press-card__title"><a href="{{ route('gazette.show', $article) }}">{{ $article->title }}</a></h3>
                            @if ($article->excerpt)
                                <p class="press-card__excerpt">{{ \Illuminate\Support\Str::limit($article->excerpt, 150) }}</p>
                            @endif
                            <a class="more-link more-link--small" href="{{ route('gazette.show', $article) }}">Lire la suite<span class="visually-hidden"> : {{ $article->title }}</span> <x-picto name="fleche" /></a>
                        </article>
                    @endforeach
                </div>

                {{ $articles->links('partials.pagination') }}
            @endif
        </div>

        <aside class="gazette-side" aria-label="Dans la Gazette">
            <form class="side-search" method="GET" action="{{ route('gazette.index') }}" role="search">
                <label class="visually-hidden" for="recherche-gazette">Rechercher un article</label>
                <input id="recherche-gazette" type="search" name="q" value="{{ $search }}" placeholder="Rechercher un article…" maxlength="80">
                <button type="submit" class="side-search__button"><x-picto name="loupe" /><span class="visually-hidden">Rechercher</span></button>
            </form>

            @if ($years->isNotEmpty())
                <section class="side-panel" aria-labelledby="titre-annees">
                    <h2 id="titre-annees" class="side-panel__title"><x-picto name="livre" />Archives</h2>
                    <ul class="side-list">
                        @foreach ($years as $itemYear => $count)
                            <li>
                                <a href="{{ route('gazette.index', ['annee' => $itemYear]) }}" @if ($year === (string) $itemYear) aria-current="page" @endif>
                                    <span>{{ $itemYear }}</span>
                                    <span class="side-list__count">{{ $count }} {{ $count > 1 ? 'articles' : 'article' }}</span>
                                    <x-picto name="chevron" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <a class="more-link more-link--small" href="{{ route('archives.index') }}">Archives municipales <x-picto name="fleche" /></a>
                </section>
            @else
                <section class="side-panel" aria-labelledby="titre-archives">
                    <h2 id="titre-archives" class="side-panel__title"><x-picto name="livre" />Archives</h2>
                    <p class="muted">Délibérations, registres et documents anciens de la mairie.</p>
                    <a class="more-link more-link--small" href="{{ route('archives.index') }}">Archives municipales <x-picto name="fleche" /></a>
                </section>
            @endif

            @if ($special)
                <section class="side-panel side-panel--dossier" aria-labelledby="titre-dossier">
                    <h2 id="titre-dossier" class="side-panel__title"><x-picto name="dossier" />Dossier spécial</h2>
                    <div class="dossier-card">
                        <span class="dossier-card__photo photo-print">
                            <x-cover :model="$special['cover']" :fallback="$ambiance::forArticle($special['cover'])" size="small" />
                        </span>
                        <div class="dossier-card__body">
                            <p class="dossier-card__name">{{ $special['name'] }}</p>
                            <p class="dossier-card__count">{{ $special['count'] }} {{ $special['count'] > 1 ? 'articles' : 'article' }}</p>
                            <a class="btn btn--small btn--ink" href="{{ route('gazette.index', ['dossier' => $special['name']]) }}">Découvrir le dossier <x-picto name="fleche" /></a>
                        </div>
                    </div>
                </section>
            @endif
        </aside>
    </div>
</x-layout>
