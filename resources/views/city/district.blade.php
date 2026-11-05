<x-layout :title="$district->name" :description="$district->summary" :robots="$isPreview ? 'noindex' : null">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['La ville' => route('city.index'), $district->name => null]" />
            <x-preview-banner :show="$isPreview" />
            <p class="eyebrow">Quartier</p>
            <h1>{{ $district->name }}</h1>
            @if ($district->summary)
                <p class="lede">{{ $district->summary }}</p>
            @endif
        </header>

        <div class="entry">
            <div>
                @if ($district->description)
                    <div class="prose">{{ \App\Support\Markdown::render($district->description) }}</div>
                @endif

                @if ($locations->isNotEmpty())
                    <section class="entry__section">
                        <h2>Les lieux du quartier</h2>
                        <div class="grid-cards">
                            @foreach ($locations as $location)
                                @include('city.partials.location-tile', ['location' => $location])
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="entry__aside" aria-label="Illustration">
                <x-figure :url="$district->imageUrl()" :credit="$district->image_credit" :alt="$district->name" />
            </aside>
        </div>
    </div>
</x-layout>
