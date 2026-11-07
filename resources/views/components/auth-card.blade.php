@props(['title', 'intro' => null])
<x-layout :title="$title" robots="noindex">
    <div class="auth-scene">
        <x-ambiance name="lanternes" eager class="auth-scene__image" />
        <div class="container auth-page">
            <div class="card card--narrow card--veil stack stack--loose">
                <div class="stack">
                    <p class="auth-scene__kanji" lang="ja" aria-hidden="true">{{ config('hollow.kanji') }}</p>
                    <h1 class="card__title">{{ $title }}</h1>
                    @if ($intro)
                        <p class="card__intro">{{ $intro }}</p>
                    @endif
                </div>
                <x-flash />
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layout>
