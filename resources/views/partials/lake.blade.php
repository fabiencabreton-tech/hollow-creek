{{-- Le lac Tsukikage la nuit : montagnes, forêt de cèdres, torii du sanctuaire Mizukami,
     brume qui glisse sur l'eau, quarante-trois lanternes jaunes et la quarante-quatrième, blanche, à l'écart. --}}
@php($heights = [204, 210, 206, 212, 207, 203, 211])
<svg class="lake" viewBox="0 0 1440 260" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="lac-brume" x1="0" x2="1" y1="0" y2="0">
            <stop offset="0" stop-color="#c9d3e6" stop-opacity="0"/>
            <stop offset="0.3" stop-color="#c9d3e6" stop-opacity="0.22"/>
            <stop offset="0.7" stop-color="#c9d3e6" stop-opacity="0.16"/>
            <stop offset="1" stop-color="#c9d3e6" stop-opacity="0"/>
        </linearGradient>
        <radialGradient id="lac-lueur">
            <stop offset="0" stop-color="#ffd27a" stop-opacity="0.55"/>
            <stop offset="1" stop-color="#ffd27a" stop-opacity="0"/>
        </radialGradient>
        <radialGradient id="lac-lueur-blanche">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.7"/>
            <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
        </radialGradient>
    </defs>
    <path class="lake__peaks" d="M0 130L110 68L190 106L300 40L410 102L520 60L640 114L760 50L880 108L1000 72L1110 120L1230 58L1340 106L1440 80V260H0Z"/>
    <path class="lake__hills" d="M0 148C180 106 320 126 480 100S820 80 980 118S1260 96 1440 126V260H0Z"/>
    <path class="lake__forest" d="M0 198L16 170L32 196L50 156L68 196L86 168L104 196L122 150L140 196L158 166L176 196L194 160L212 196L230 172L248 196L266 154L284 196L302 166L320 196L338 150L356 196L374 170L392 196L410 158L428 196L446 172L464 196L482 156L500 196L518 168L536 196L554 150L572 196L590 164L608 196L626 160L644 196L662 172L680 196L698 154L716 196L734 166L752 196L770 150L788 196L806 170L824 196L842 158L860 196L878 172L896 196L914 156L932 196L950 168L968 196L986 150L1004 196L1022 164L1040 196L1058 160L1076 196L1094 172L1112 196L1130 154L1148 196L1166 166L1184 196L1202 150L1220 196L1238 170L1256 196L1274 158L1292 196L1310 172L1328 196L1346 156L1364 196L1382 168L1400 196L1418 152L1440 196V260H0Z"/>
    <rect class="lake__water" x="0" y="196" width="1440" height="64"/>
    <g class="lake__torii">
        <path d="M1150 160C1180 166 1250 166 1280 160L1278 168C1250 172 1180 172 1152 168Z"/>
        <rect x="1160" y="176" width="110" height="5"/>
        <rect x="1172" y="168" width="7" height="58"/>
        <rect x="1251" y="168" width="7" height="58"/>
        <rect x="1211" y="168" width="8" height="10"/>
    </g>
    <path class="lake__ripple" d="M90 214h120M380 236h160M700 217h90M980 239h140M1160 232h110M1300 224h90M240 248h100M560 252h140" stroke-width="1"/>
    <g class="lake__lanterns">
        @for ($i = 0; $i < 43; $i++)
            @php($x = 104 + 24 * $i)
            @php($y = $heights[$i % 7])
            <g class="lake__lantern">
                <circle class="lake__glow" cx="{{ $x + 3 }}" cy="{{ $y + 3 }}" r="11" fill="url(#lac-lueur)"/>
                <rect class="lake__paper" x="{{ $x }}" y="{{ $y }}" width="6" height="7" rx="1.2"/>
                <rect class="lake__reflet" x="{{ $x + 1 }}" y="{{ $y + 10 }}" width="4" height="5" rx="1"/>
            </g>
        @endfor
    </g>
    <g class="lake__lantern lake__lantern--white">
        <circle class="lake__glow" cx="1384" cy="224" r="18" fill="url(#lac-lueur-blanche)"/>
        <rect class="lake__paper" x="1379" y="219" width="10" height="11" rx="1.8"/>
        <rect class="lake__reflet" x="1381" y="233" width="6" height="7" rx="1"/>
    </g>
    <rect class="lake__mist lake__mist--one" x="-200" y="186" width="1100" height="34" fill="url(#lac-brume)"/>
    <rect class="lake__mist lake__mist--two" x="600" y="204" width="1000" height="26" fill="url(#lac-brume)"/>
</svg>
