<x-layout title="Recherche" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'Recherche' => null]" />
            <h1>Recherche</h1>
        </header>

        <div class="stack page-body stack--loose">
            <form class="filters" method="GET" action="{{ route('search') }}" role="search">
                <div class="field search-field">
                    <label for="recherche-site">Personnages, scènes, célébrités, lieux, Gazette</label>
                    <input id="recherche-site" type="search" name="q" value="{{ $term }}" maxlength="80" required minlength="2">
                </div>
                <div class="form-actions"><button type="submit" class="btn">Rechercher</button></div>
            </form>

            @if ($results !== null)
                @php($total = collect($results)->sum(fn ($items) => $items->count()))
                <p class="muted" role="status">{{ $total }} {{ $total > 1 ? 'résultats' : 'résultat' }} pour « {{ $term }} ».</p>

                @if ($results['characters']->isNotEmpty())
                    <section aria-labelledby="res-personnages">
                        <h2 id="res-personnages" class="section__title">Personnages</h2>
                        <ul class="portrait-grid plain-list">
                            @foreach ($results['characters'] as $character)
                                <li>
                                    <a class="portrait-card" href="{{ route('characters.show', $character) }}">
                                        <x-portrait :character="$character" />
                                        <span class="portrait-card__name">{{ $character->fullName() }}</span>
                                        <span class="portrait-card__meta">{{ $character->species?->name }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($results['scenes']->isNotEmpty())
                    <section aria-labelledby="res-scenes">
                        <h2 id="res-scenes" class="section__title">Scènes</h2>
                        <div class="scene-list">
                            @foreach ($results['scenes'] as $scene)
                                @include('scenes.partials.card', ['scene' => $scene])
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($results['faceclaims']->isNotEmpty())
                    <section aria-labelledby="res-bottin">
                        <h2 id="res-bottin" class="section__title">Bottin des avatars</h2>
                        <ul class="plain-list stack stack--tight">
                            @foreach ($results['faceclaims'] as $faceclaim)
                                <li class="cluster">
                                    <a href="{{ route('faceclaims.index', ['q' => $faceclaim->name]) }}">{{ $faceclaim->name }}</a>
                                    @if ($faceclaim->activeClaim)
                                        <x-status-badge :status="$faceclaim->activeClaim->status" />
                                    @else
                                        <span class="badge badge--ok">Disponible</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($results['locations']->isNotEmpty())
                    <section aria-labelledby="res-lieux">
                        <h2 id="res-lieux" class="section__title">Lieux</h2>
                        <ul class="plain-list stack stack--tight">
                            @foreach ($results['locations'] as $location)
                                <li><a href="{{ route('locations.show', $location) }}">{{ $location->name }}</a>@if ($location->summary)<span class="muted"> · {{ \Illuminate\Support\Str::limit($location->summary, 100) }}</span>@endif</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($results['articles']->isNotEmpty())
                    <section aria-labelledby="res-gazette">
                        <h2 id="res-gazette" class="section__title">Gazette</h2>
                        <ul class="plain-list stack stack--tight">
                            @foreach ($results['articles'] as $article)
                                <li><a href="{{ route('gazette.show', $article) }}">{{ $article->title }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            @elseif ($term !== '')
                <p class="muted">Tapez au moins deux caractères.</p>
            @endif
        </div>
    </div>
</x-layout>
