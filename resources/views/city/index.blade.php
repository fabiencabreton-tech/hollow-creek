<x-layout title="La ville" description="Les quartiers et les lieux de Hollow Creek.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'La ville' => null]" />
            <p class="eyebrow">Hollow Creek · environ 8 000 habitants</p>
            <h1>{{ $page?->title ?? 'La ville' }}</h1>
        </header>

        @if ($page)
            <div class="prose">{{ $page->renderedBody() }}</div>
            <p class="fleuron" aria-hidden="true">✦</p>
        @endif

        <div class="page-body">
            @foreach ($districts as $district)
                <section class="district-block" aria-labelledby="quartier-{{ $district->id }}">
                    <div class="district-block__header">
                        <h2 id="quartier-{{ $district->id }}">{{ $district->name }}</h2>
                        <a href="{{ route('districts.show', $district) }}">Le quartier</a>
                    </div>
                    @if ($district->summary)
                        <p class="lede">{{ $district->summary }}</p>
                    @endif
                    @if ($district->locations->isNotEmpty())
                        <div class="grid-cards">
                            @foreach ($district->locations as $location)
                                @include('city.partials.location-tile', ['location' => $location])
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach

            @if ($others->isNotEmpty())
                <section class="district-block" aria-labelledby="quartier-autres">
                    <div class="district-block__header">
                        <h2 id="quartier-autres">{{ $districts->isEmpty() ? 'Les lieux' : 'Autres lieux de Hollow Creek' }}</h2>
                    </div>
                    <div class="grid-cards">
                        @foreach ($others as $location)
                            @include('city.partials.location-tile', ['location' => $location])
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($districts->isEmpty() && $others->isEmpty())
                <p class="empty-note">Les lieux de Hollow Creek seront présentés ici très bientôt.</p>
            @endif
        </div>
    </div>
</x-layout>
