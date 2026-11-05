<x-layout :title="$article->title" :description="$article->excerpt" :robots="$isPreview ? 'noindex' : null">
    <div class="container">
        <header class="masthead">
            <p class="masthead__title"><a href="{{ route('gazette.index') }}">The Hollow Creek Gazette</a></p>
        </header>

        <article>
            <header class="article-header">
                <x-preview-banner :show="$isPreview" />
                <p class="eyebrow"><a href="{{ route('gazette.index', ['rubrique' => $article->rubric->value]) }}">{{ $article->rubric->label() }}</a></p>
                <h1>{{ $article->title }}</h1>
                <p class="byline">Par {{ $article->byline }}@if ($article->rp_date) · {{ $article->rp_date }} @endif</p>
                @if ($article->excerpt)
                    <p class="lede">{{ $article->excerpt }}</p>
                @endif
            </header>

            <div class="article-body stack stack--loose">
                @if ($article->imageUrl())
                    <x-figure :url="$article->imageUrl()" :credit="$article->image_credit" :alt="''" />
                @endif

                <div class="prose">{{ \App\Support\Markdown::render($article->body) }}</div>

                @if ($location)
                    <p class="muted">Lieu : <a href="{{ route('locations.show', $location) }}">{{ $location->name }}</a></p>
                @endif

                <p><a href="{{ route('gazette.index') }}">← Tous les articles</a></p>
            </div>
        </article>
    </div>
</x-layout>
