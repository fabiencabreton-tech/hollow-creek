<x-layout title="La Gazette de Tsukimori" description="Le journal local de Tsukimori : actualités, faits divers, vie locale, annonces et avis de décès.">
    <div class="container">
        <header class="masthead">
            <p class="masthead__title"><a href="{{ route('gazette.index') }}">La Gazette de Tsukimori</a></p>
            <p class="masthead__line">
                <span>{{ $rpDate }}</span>
                <span>Le journal de Tsukimori</span>
            </p>
        </header>

        <nav aria-label="Rubriques">
            <ul class="rubrics">
                <li><a href="{{ route('gazette.index') }}" @if (! $rubric) aria-current="page" @endif>Tout</a></li>
                @foreach ($rubrics as $item)
                    <li><a href="{{ route('gazette.index', ['rubrique' => $item->value]) }}" @if ($rubric === $item) aria-current="page" @endif>{{ $item->label() }}</a></li>
                @endforeach
            </ul>
        </nav>

        <h1 class="visually-hidden">La Gazette de Tsukimori{{ $rubric ? ' — '.$rubric->label() : '' }}</h1>

        @if ($featured)
            <article class="lead-story" aria-labelledby="article-une">
                <div class="stack">
                    <p class="eyebrow">{{ $featured->rubric->label() }}</p>
                    <h2 id="article-une" class="lead-story__title"><a href="{{ route('gazette.show', $featured) }}">{{ $featured->title }}</a></h2>
                    <p class="byline">Par {{ $featured->byline }}@if ($featured->rp_date) · {{ $featured->rp_date }} @endif</p>
                    @if ($featured->excerpt)
                        <p class="lede">{{ $featured->excerpt }}</p>
                    @endif
                    <p><a href="{{ route('gazette.show', $featured) }}">Lire l’article</a></p>
                </div>
                @if ($featured->imageUrl())
                    <x-figure :url="$featured->imageUrl()" :credit="$featured->image_credit" :alt="''" />
                @endif
            </article>
        @endif

        @if ($articles->isEmpty() && ! $featured)
            <div class="empty-note">
                @include('partials.stopped-clock')
                <p>{{ $rubric ? 'Aucun article dans cette rubrique pour le moment.' : 'Le premier numéro de la Gazette est en préparation.' }}</p>
            </div>
        @else
            <div class="article-list">
                @foreach ($articles as $article)
                    <article class="article-card">
                        <p class="eyebrow">{{ $article->rubric->label() }}</p>
                        <h2 class="article-card__title"><a href="{{ route('gazette.show', $article) }}">{{ $article->title }}</a></h2>
                        <p class="byline">Par {{ $article->byline }}@if ($article->rp_date) · {{ $article->rp_date }} @endif</p>
                        @if ($article->excerpt)
                            <p>{{ $article->excerpt }}</p>
                        @endif
                    </article>
                @endforeach
            </div>

            {{ $articles->links('partials.pagination') }}
        @endif
    </div>
</x-layout>
