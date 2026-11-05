<x-layout :title="$page->title">
    <div class="container">
        <header class="page-header">
            <p class="eyebrow">Pages officielles</p>
            <h1>{{ $page->title }}</h1>
            <p class="muted">Mis à jour le {{ $page->updated_at?->locale('fr')->isoFormat('LL') }}</p>
        </header>

        <article class="page-body prose">
            {{ $page->renderedBody() }}
        </article>
    </div>
</x-layout>
