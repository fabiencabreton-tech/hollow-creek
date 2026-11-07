@props(['model', 'fallback', 'size' => null])
{{-- Image d'un contenu (article, famille, espèce) envoyée par l'équipe, sinon illustration d'ambiance. --}}
@php($url = $model->imageUrl())
@if ($url)
    <img {{ $attributes->merge(['class' => 'cover']) }} src="{{ $url }}" alt="" loading="lazy" decoding="async">
@else
    <x-ambiance :name="$fallback" :size="$size" :attributes="$attributes->merge(['class' => 'cover cover--ambiance'])" />
@endif
