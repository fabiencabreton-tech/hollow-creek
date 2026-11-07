@props(['name', 'label' => null])
{{-- Pictogramme au trait du jeu partials/icons. Décoratif, sauf si un libellé est donné. --}}
<svg {{ $attributes->merge(['class' => 'icon icon--'.$name]) }} viewBox="0 0 24 24" @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif focusable="false"><use href="#i-{{ $name }}"/></svg>
