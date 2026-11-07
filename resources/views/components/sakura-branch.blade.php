{{-- Branche de cerisier en fleurs, ornement des coins (dessinée pour Tsukimori).
     La fleur (#sb-fleur) est définie une fois dans partials/icons. --}}
@php
    $flowers = [
        [146, 9, 1.35, 10], [131, 20, 1.0, 40], [180, 124, 1.25, 0], [171, 103, 0.9, 25],
        [305, 104, 1.1, 15], [313, 147, 1.35, 5], [86, 74, 1.2, 30], [70, 57, 0.85, 50],
        [231, 92, 1.0, 35], [205, 78, 0.8, 12], [117, 46, 0.9, 60], [286, 129, 0.95, 20],
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'sakura-branch']) }} viewBox="0 0 360 240" aria-hidden="true" focusable="false">
    <g class="sakura-branch__wood" fill="none" stroke-linecap="round">
        <path d="M-12 15C30 20 70 32 110 44" stroke-width="7"/>
        <path d="M110 44C150 56 190 70 230 92" stroke-width="5"/>
        <path d="M230 92C260 108 285 125 312 147" stroke-width="3"/>
        <path d="M96 40C108 22 124 12 146 9" stroke-width="3"/>
        <path d="M170 62C178 82 182 100 180 124" stroke-width="2.5"/>
        <path d="M250 103C268 97 286 99 305 104" stroke-width="2"/>
        <path d="M60 30C64 48 72 62 86 74" stroke-width="2.5"/>
    </g>
    <g class="sakura-branch__flowers">
        @foreach ($flowers as [$x, $y, $scale, $angle])
            <g transform="translate({{ $x }} {{ $y }}) rotate({{ $angle }}) scale({{ $scale }})">
                <use href="#sb-fleur" class="sakura-branch__petals"/>
                <circle r="1.7" class="sakura-branch__heart"/>
            </g>
        @endforeach
        <ellipse class="sakura-branch__bud" cx="158" cy="5" rx="2.4" ry="3.4" transform="rotate(60 158 5)"/>
        <ellipse class="sakura-branch__bud" cx="322" cy="157" rx="2.2" ry="3.2" transform="rotate(-30 322 157)"/>
        <ellipse class="sakura-branch__bud" cx="183" cy="138" rx="2" ry="3" />
    </g>
</svg>
