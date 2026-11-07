@props(['name', 'eager' => false, 'size' => null])
{{-- Illustration d'ambiance (décorative), en deux largeurs ; size="small" pour une vignette. --}}
@php($picture = \App\Support\Ambiance::picture($name))
<picture {{ $attributes->merge(['class' => 'ambiance']) }} data-ambiance="{{ $name }}">
    @if ($size !== 'small')
        <source media="(max-width: 760px)" srcset="{{ $picture['small'] }}">
    @endif
    <img src="{{ $size === 'small' ? $picture['small'] : $picture['large'] }}" alt="" width="{{ $picture['width'] }}" height="{{ $picture['height'] }}" @if ($eager) fetchpriority="high" @else loading="lazy" @endif decoding="async">
</picture>
