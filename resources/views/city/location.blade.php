@php
    $crumbs = ['La ville' => route('city.index')];
    if ($district) {
        $crumbs[$district->name] = route('districts.show', $district);
    }
    $crumbs[$location->name] = null;
@endphp
<x-layout :title="$location->name" :description="$location->summary" :robots="$isPreview ? 'noindex' : null">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="$crumbs" />
            <x-preview-banner :show="$isPreview" />
            <p class="eyebrow">{{ $location->kind ?: 'Lieu' }}{{ $district ? ' · '.$district->name : '' }}</p>
            <h1>{{ $location->name }}</h1>
            @if ($location->summary)
                <p class="lede">{{ $location->summary }}</p>
            @endif
        </header>

        <div class="entry">
            <div>
                @if ($location->description)
                    <div class="prose">{{ \App\Support\Markdown::render($location->description) }}</div>
                @endif

                @if ($articles->isNotEmpty())
                    <section class="entry__section">
                        <h2>Dans la Gazette</h2>
                        <ul class="stack plain-list">
                            @foreach ($articles as $article)
                                <li>
                                    <a href="{{ route('gazette.show', $article) }}">{{ $article->title }}</a>
                                    <span class="muted"> · {{ $article->rp_date ?: $article->published_at?->locale('fr')->isoFormat('LL') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <aside class="entry__aside" aria-label="Illustration">
                <x-figure :url="$location->imageUrl()" :credit="$location->image_credit" :alt="$location->name" :ambiance="\App\Support\Ambiance::forPlace($location)" />
                <div class="card stack">
                    <p class="eyebrow">Scènes en cours ici</p>
                    @if ($scenes->isEmpty())
                        <p class="muted">Aucune scène publique en cours dans ce lieu.</p>
                    @else
                        <ul class="plain-list stack stack--tight">
                            @foreach ($scenes as $scene)
                                <li><a href="{{ route('scenes.show', $scene) }}">{{ $scene->title }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                    @auth
                        <div><a class="btn btn--ghost" href="{{ route('scenes.create', ['lieu' => $location->slug]) }}">Ouvrir une scène ici</a></div>
                    @endauth
                </div>
            </aside>
        </div>
    </div>
</x-layout>
