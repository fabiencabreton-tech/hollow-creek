<x-layout :title="$investigation->title" :description="$investigation->summary" :robots="$isPreview ? 'noindex' : null">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Enquêtes' => route('investigations.index'), $investigation->title => null]" />
            <x-preview-banner :show="$isPreview" />
            <p class="eyebrow">Enquête · {{ $investigation->status->getLabel() }}</p>
            <h1>{{ $investigation->title }}</h1>
            @if ($investigation->summary)
                <p class="lede">{{ $investigation->summary }}</p>
            @endif
        </header>

        <div class="entry">
            <div class="stack stack--loose">
                @if ($investigation->body)
                    <div class="prose">{{ \App\Support\Markdown::render($investigation->body) }}</div>
                @endif

                <section class="entry__section" aria-labelledby="indices-publics">
                    <h2 id="indices-publics">Indices connus de tous</h2>
                    @if ($publicClues->isEmpty())
                        <p class="muted">Aucun indice n’a encore été rendu public.</p>
                    @else
                        @include('investigations.partials.clue-list', ['clues' => $publicClues])
                    @endif
                </section>

                @if ($privateClues->isNotEmpty())
                    <section class="entry__section" aria-labelledby="indices-detenus">
                        <h2 id="indices-detenus">Ce que vos personnages savent</h2>
                        <p class="muted">Ces indices ne sont visibles que de vous.</p>
                        @include('investigations.partials.clue-list', ['clues' => $privateClues])
                    </section>
                @endif

                @if ($staffClues->isNotEmpty())
                    <section class="entry__section" aria-labelledby="indices-equipe">
                        <h2 id="indices-equipe">Indices confiés (vue de l’équipe)</h2>
                        @include('investigations.partials.clue-list', ['clues' => $staffClues])
                    </section>
                @endif
            </div>

            <aside class="entry__aside" aria-label="Illustration">
                <x-figure :url="$investigation->imageUrl()" :credit="$investigation->image_credit" :alt="''" ambiance="foret-brume" />
                @if ($location)
                    <p class="muted">Lieu : <a href="{{ route('locations.show', $location) }}">{{ $location->name }}</a></p>
                @endif
            </aside>
        </div>
    </div>
</x-layout>
