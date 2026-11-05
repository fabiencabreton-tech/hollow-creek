<x-layout :title="$species->name" :description="$species->summary" :robots="$isPreview ? 'noindex' : null">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['L’univers' => route('universe.index'), 'Les espèces' => route('species.index'), $species->name => null]" />
            <x-preview-banner :show="$isPreview" />
            <p class="eyebrow">Espèce jouable</p>
            <h1>{{ $species->name }}</h1>
            @if ($species->summary)
                <p class="lede">{{ $species->summary }}</p>
            @endif
        </header>

        <div class="entry">
            <div>
                @if ($species->description)
                    <section class="entry__section prose">{{ \App\Support\Markdown::render($species->description) }}</section>
                @endif
                @foreach (['abilities' => 'Capacités', 'limits' => 'Limites', 'story_hooks' => 'Pistes de jeu'] as $field => $heading)
                    @if (filled($species->{$field}))
                        <section class="entry__section">
                            <h2>{{ $heading }}</h2>
                            <div class="prose">{{ \App\Support\Markdown::render($species->{$field}) }}</div>
                        </section>
                    @endif
                @endforeach
            </div>

            <aside class="entry__aside" aria-label="Illustration et autres espèces">
                <x-figure :url="$species->imageUrl()" :credit="$species->image_credit" :alt="$species->name" />
                @if ($others->isNotEmpty())
                    <nav class="card stack" aria-label="Autres espèces">
                        <p class="eyebrow">Autres espèces</p>
                        <ul class="stack stack--tight plain-list">
                            @foreach ($others as $other)
                                <li><a href="{{ route('species.show', $other) }}">{{ $other->name }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            </aside>
        </div>
    </div>
</x-layout>
