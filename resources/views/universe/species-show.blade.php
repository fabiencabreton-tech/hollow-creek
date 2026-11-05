<x-layout :title="$species->name" :description="$species->summary" :robots="$isPreview ? 'noindex' : null">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['L’univers' => route('universe.index'), 'Le bestiaire' => route('species.index'), $species->name => null]" />
            <x-preview-banner :show="$isPreview" />
            <div class="cluster">
                <p class="eyebrow">{{ $species->kind->getLabel() }}@if ($species->parent) · catégorie particulière de <a href="{{ route('species.show', $species->parent) }}">{{ $species->parent->name }}</a>@endif</p>
                @if ($species->is_rare)<span class="badge">Rare · validation renforcée</span>@endif
                @if ($species->needs_registry)<span class="badge">Registre de Kirisawa</span>@endif
            </div>
            <h1>{{ $species->name }}</h1>
            @if ($species->summary)
                <p class="lede">{{ $species->summary }}</p>
            @endif
            @if ($species->allowed_natures)
                <p class="muted">S’ajoute à la nature : {{ $allowedNatures->pluck('name')->join(', ', ' ou ') }}.</p>
            @endif
        </header>

        <div class="entry">
            <div>
                @php($filled = collect(\App\Models\Species::SECTIONS)->filter(fn ($label, $field) => filled($species->{$field})))
                @if ($filled->count() > 2)
                    <nav class="card bestiary-toc" aria-label="Rubriques de la fiche">
                        <ul class="plain-list cluster">
                            @foreach ($filled as $field => $label)
                                <li><a href="#{{ $field }}">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                @foreach ($filled as $field => $label)
                    <section id="{{ $field }}" class="entry__section">
                        <h2>{{ $label }}</h2>
                        <div class="prose">{{ \App\Support\Markdown::render($species->{$field}) }}</div>
                    </section>
                @endforeach

                @if (filled($species->story_hooks))
                    <section class="entry__section">
                        <h2>Pistes de jeu</h2>
                        <div class="prose">{{ \App\Support\Markdown::render($species->story_hooks) }}</div>
                    </section>
                @endif

                @if ($species->needs_registry)
                    <p><a class="btn btn--ghost" href="{{ route('kirisawa.index') }}">Consulter le registre de Kirisawa</a></p>
                @endif
            </div>

            <aside class="entry__aside" aria-label="Illustration et autres catégories">
                <x-figure :url="$species->imageUrl()" :credit="$species->image_credit" :alt="$species->name" />
                @if ($others->isNotEmpty())
                    <nav class="card stack" aria-label="Autres catégories">
                        <p class="eyebrow">Autres catégories</p>
                        <ul class="stack stack--tight plain-list">
                            @foreach ($others as $other)
                                <li><a href="{{ route('species.show', $other) }}">{{ $other->name }}</a> <span class="muted">· {{ $other->kind->getLabel() }}</span></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            </aside>
        </div>
    </div>
</x-layout>
