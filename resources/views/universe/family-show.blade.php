<x-layout :title="$family->name" :description="$family->summary" :robots="$isPreview ? 'noindex' : null">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['L’univers' => route('universe.index'), 'Les familles fondatrices' => route('families.index'), $family->name => null]" />
            <x-preview-banner :show="$isPreview" />
            <div class="cluster">
                <span class="seal seal--large" aria-hidden="true">{{ $family->initial() }}</span>
                <div class="stack stack--tight">
                    <p class="eyebrow">Famille fondatrice</p>
                    <h1>{{ $family->name }}</h1>
                </div>
            </div>
            @if ($family->summary)
                <p class="lede">{{ $family->summary }}</p>
            @endif
        </header>

        <div class="entry">
            <div>
                @if ($family->history)
                    <section class="entry__section">
                        <h2>Histoire</h2>
                        <div class="prose">{{ \App\Support\Markdown::render($family->history) }}</div>
                    </section>
                @endif
                @if ($family->open_roles)
                    <section class="entry__section">
                        <h2>Rôles ouverts</h2>
                        <div class="prose">{{ \App\Support\Markdown::render($family->open_roles) }}</div>
                    </section>
                @endif
            </div>

            <aside class="entry__aside" aria-label="Illustration et autres familles">
                <x-figure :url="$family->imageUrl()" :credit="$family->image_credit" :alt="$family->name" />
                @if ($others->isNotEmpty())
                    <nav class="card stack" aria-label="Autres familles">
                        <p class="eyebrow">Autres familles</p>
                        <ul class="stack stack--tight plain-list">
                            @foreach ($others as $other)
                                <li><a href="{{ route('families.show', $other) }}">{{ $other->name }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            </aside>
        </div>
    </div>
</x-layout>
