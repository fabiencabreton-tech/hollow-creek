<x-layout title="La ville" description="Les quartiers et les lieux de Tsukimori.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'La ville' => null]" />
            <p class="eyebrow">Tsukimori · environ 8 000 habitants</p>
            <h1>{{ $page?->title ?? 'La ville' }}</h1>
        </header>

        @if ($page)
            <div class="prose">{{ $page->renderedBody() }}</div>
            <p class="fleuron" aria-hidden="true">✦</p>
        @endif

        <div class="page-body">
            @if ($places->isNotEmpty())
                @include('city.partials.map')
            @endif

            <section class="city-life" aria-labelledby="vie-quotidienne">
                <h2 id="vie-quotidienne">Vivre à Tsukimori</h2>
                <div class="grid-cards">
                    <a class="tile" href="{{ route('residences.index') }}">
                        <span class="tile__title">Habitations</span>
                        <span class="tile__text">Où vivent les personnages, et les maisons qui attendent encore quelqu’un.</span>
                    </a>
                    <a class="tile" href="{{ route('ads.index') }}">
                        <span class="tile__title">Petites annonces</span>
                        <span class="tile__text">Emplois, ventes, objets perdus et messages personnels des habitants.</span>
                    </a>
                    <a class="tile" href="{{ route('archives.index') }}">
                        <span class="tile__title">Archives municipales</span>
                        <span class="tile__text">Délibérations, état civil, cadastre et coupures de presse conservés à la mairie.</span>
                    </a>
                    <a class="tile" href="{{ route('investigations.index') }}">
                        <span class="tile__title">Enquêtes</span>
                        <span class="tile__text">Les mystères ouverts et les indices déjà réunis.</span>
                    </a>
                </div>
            </section>

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
                        <h2 id="quartier-autres">{{ $districts->isEmpty() ? 'Les lieux' : 'Autres lieux de Tsukimori' }}</h2>
                    </div>
                    <div class="grid-cards">
                        @foreach ($others as $location)
                            @include('city.partials.location-tile', ['location' => $location])
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($districts->isEmpty() && $others->isEmpty())
                <p class="empty-note">Les lieux de Tsukimori seront présentés ici très bientôt.</p>
            @endif
        </div>
    </div>
</x-layout>
