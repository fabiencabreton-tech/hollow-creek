@props([
    'image' => 'foret-brume',
    'title',
    'eyebrow' => null,
    'kanji' => null,
    'lead' => null,
    'breadcrumbs' => null,
])
{{-- Bandeau illustré d'une section : illustration d'ambiance, titre, nœud, chapeau. --}}
<header {{ $attributes->merge(['class' => 'page-hero page-hero--'.$image]) }}>
    <x-ambiance :name="$image" eager class="page-hero__image" />
    <div class="container page-hero__inner">
        @if ($breadcrumbs)
            <x-breadcrumbs :items="$breadcrumbs" />
        @endif
        @if ($eyebrow)
            <p class="page-hero__eyebrow">{{ $eyebrow }}</p>
        @endif
        @if ($kanji)
            <p class="page-hero__kanji" lang="ja" aria-hidden="true">{{ $kanji }}</p>
        @endif
        <h1 class="page-hero__title">{{ $title }}</h1>
        <x-ornament class="page-hero__ornament" />
        @if ($lead)
            <p class="page-hero__lead">{{ $lead }}</p>
        @endif
        {{ $slot }}
    </div>
</header>
