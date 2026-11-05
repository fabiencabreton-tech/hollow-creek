@props(['title', 'intro' => null])
<x-layout :title="$title" robots="noindex">
    <div class="container auth-page">
        <div class="card card--narrow stack stack--loose">
            <div class="stack">
                <h1 class="card__title">{{ $title }}</h1>
                @if ($intro)
                    <p class="card__intro">{{ $intro }}</p>
                @endif
            </div>
            <x-flash />
            {{ $slot }}
        </div>
    </div>
</x-layout>
