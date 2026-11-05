@props(['character', 'size' => 'medium'])
@php
    $treatment = $character->species?->treatment?->value ?? 'humain';
    $url = $character->avatarUrl();
@endphp
<span {{ $attributes->class(['portrait', 'portrait--'.$treatment, 'portrait--taille-'.$size]) }}>
    @if ($url)
        <img src="{{ $url }}" alt="{{ $character->fullName() }}" loading="lazy" decoding="async">
    @else
        <span class="portrait__empty" aria-hidden="true">{{ mb_substr($character->first_name, 0, 1) }}</span>
    @endif
    @if ($treatment === 'revenant')
        <span class="portrait__mark" aria-hidden="true">1996</span>
    @endif
</span>
