{{-- Le lac de Hollow Creek, la forêt et les quarante-trois lanternes ; la quarante-quatrième brille à part. --}}
@php($heights = [198, 203, 200, 206, 201, 197, 204])
<svg class="lake" viewBox="0 0 1440 220" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <path class="lake__hills" d="M0 118C180 74 320 94 480 66S820 44 980 84S1260 62 1440 92V220H0Z"/>
    <path class="lake__forest" d="M0 172L20 150L40 170L60 134L80 170L100 146L120 170L140 126L160 170L180 142L200 170L220 138L240 170L260 150L280 170L300 130L320 170L340 144L360 170L380 128L400 170L420 148L440 170L460 136L480 170L500 150L520 170L540 134L560 170L580 146L600 170L620 126L640 170L660 142L680 170L700 138L720 170L740 150L760 170L780 130L800 170L820 144L840 170L860 128L880 170L900 148L920 170L940 136L960 170L980 150L1000 170L1020 134L1040 170L1060 146L1080 170L1100 126L1120 170L1140 142L1160 170L1180 138L1200 170L1220 150L1240 170L1260 130L1280 170L1300 144L1320 170L1340 128L1360 170L1380 148L1400 170L1420 136L1440 170V220H0Z"/>
    <rect class="lake__water" x="0" y="184" width="1440" height="36"/>
    <path d="M90 191h120M380 213h160M700 194h90M980 214h140M1210 209h110" stroke="#a9b2ae" stroke-opacity="0.18" stroke-width="1"/>
    <g fill="#d8a657">
        @for ($i = 0; $i < 43; $i++)
            <circle cx="{{ 150 + 27 * $i }}" cy="{{ $heights[$i % 7] }}" r="2.4"/>
        @endfor
    </g>
    <circle cx="1352" cy="192" r="10" fill="#d8a657" fill-opacity="0.16"/>
    <circle cx="1352" cy="192" r="3.6" fill="#d8a657"/>
</svg>
