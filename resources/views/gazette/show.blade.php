@php($ambiance = \App\Support\Ambiance::class)
<x-layout :title="$article->title" :description="$article->excerpt" :robots="$isPreview ? 'noindex' : null">
    <header class="gazette-strip">
        <x-ambiance name="gazette" class="gazette-strip__image" />
        <div class="container gazette-strip__inner">
            <a class="gazette-strip__title" href="{{ route('gazette.index') }}">
                <span class="gazette-strip__kanji" lang="ja" aria-hidden="true">月森新聞</span>
                <span class="gazette-strip__name">La Gazette de Tsukimori</span>
            </a>
        </div>
    </header>

    <div class="container article-layout">
        <article class="paper-article">
            <x-preview-banner :show="$isPreview" />
            <header class="paper-article__header">
                <p class="paper-article__rubric">
                    <a href="{{ route('gazette.index', ['rubrique' => $article->rubric->value]) }}">{{ $article->rubric->label() }}</a>
                    @if ($article->dossier)
                        <span aria-hidden="true">·</span> Dossier <a href="{{ route('gazette.index', ['dossier' => $article->dossier]) }}">{{ $article->dossier }}</a>
                    @endif
                </p>
                <h1 class="paper-article__title">{{ $article->title }}</h1>
                <p class="byline">Par {{ $article->byline }}@if ($article->rp_date) · {{ $article->rp_date }} @endif</p>
                <x-ornament class="paper-article__ornament" />
                @if ($article->excerpt)
                    <p class="paper-article__lede">{{ $article->excerpt }}</p>
                @endif
            </header>

            @if ($article->imageUrl())
                <figure class="paper-article__figure photo-print">
                    <img src="{{ $article->imageUrl() }}" alt="" loading="lazy" decoding="async">
                    @if ($article->image_credit)
                        <figcaption>Crédit : {{ $article->image_credit }}</figcaption>
                    @endif
                </figure>
            @endif

            <div class="prose paper-article__body">{{ \App\Support\Markdown::render($article->body) }}</div>

            @if (($article->event && $article->event->is_published) || $location)
                <ul class="paper-article__links">
                    @if ($article->event && $article->event->is_published)
                        <li><x-picto name="calendrier" />Événement : <a href="{{ route('events.show', $article->event) }}">{{ $article->event->title }}</a></li>
                    @endif
                    @if ($location)
                        <li><x-picto name="repere" />Lieu : <a href="{{ route('locations.show', $location) }}">{{ $location->name }}</a></li>
                    @endif
                </ul>
            @endif

            <p class="paper-article__back"><a href="{{ route('gazette.index') }}">← Tous les articles</a></p>
        </article>

        @if ($related->isNotEmpty() || $others->isNotEmpty())
            <aside class="gazette-side" aria-label="À lire aussi">
                @if ($related->isNotEmpty())
                    <section class="side-panel" aria-labelledby="titre-meme-dossier">
                        <h2 id="titre-meme-dossier" class="side-panel__title"><x-picto name="dossier" />Dans le même dossier</h2>
                        <ul class="side-list side-list--articles">
                            @foreach ($related as $item)
                                <li><a href="{{ route('gazette.show', $item) }}"><span>{{ $item->title }}</span><x-picto name="chevron" /></a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
                @if ($others->isNotEmpty())
                    <section class="side-panel" aria-labelledby="titre-a-lire">
                        <h2 id="titre-a-lire" class="side-panel__title"><x-picto name="journal" />À lire aussi</h2>
                        <ul class="latest latest--compact">
                            @foreach ($others as $item)
                                <li>
                                    <a class="latest__item" href="{{ route('gazette.show', $item) }}">
                                        <x-cover :model="$item" :fallback="$ambiance::forArticle($item)" size="small" class="latest__thumb" />
                                        <span class="latest__body">
                                            <span class="latest__rubric">{{ $item->rubric->label() }}</span>
                                            <span class="latest__title">{{ $item->title }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </aside>
        @endif
    </div>
</x-layout>
