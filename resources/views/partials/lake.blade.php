{{-- Le lac Tsukikage la nuit : les montagnes de Nagano, la forêt de cèdres, le torii du
     sanctuaire Mizukami dans l'eau et les quarante-trois lanternes ; la quarante-quatrième flotte à part. --}}
@php($heights = [200, 206, 202, 209, 203, 199, 207])
<svg class="lake" viewBox="0 0 1440 240" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
    <path class="lake__peaks" d="M0 120L110 58L190 96L300 30L410 92L520 50L640 104L760 40L880 98L1000 62L1110 110L1230 48L1340 96L1440 70V240H0Z"/>
    <path class="lake__hills" d="M0 138C180 96 320 116 480 90S820 70 980 108S1260 86 1440 116V240H0Z"/>
    <path class="lake__forest" d="M0 188L16 160L32 186L50 146L68 186L86 158L104 186L122 140L140 186L158 156L176 186L194 150L212 186L230 162L248 186L266 144L284 186L302 156L320 186L338 140L356 186L374 160L392 186L410 148L428 186L446 162L464 186L482 146L500 186L518 158L536 186L554 140L572 186L590 154L608 186L626 150L644 186L662 162L680 186L698 144L716 186L734 156L752 186L770 140L788 186L806 160L824 186L842 148L860 186L878 162L896 186L914 146L932 186L950 158L968 186L986 140L1004 186L1022 154L1040 186L1058 150L1076 186L1094 162L1112 186L1130 144L1148 186L1166 156L1184 186L1202 140L1220 186L1238 160L1256 186L1274 148L1292 186L1310 162L1328 186L1346 146L1364 186L1382 158L1400 186L1418 142L1440 186V240H0Z"/>
    <rect class="lake__water" x="0" y="196" width="1440" height="44"/>
    {{-- Torii dans l'eau --}}
    <g class="lake__torii">
        <path d="M1150 150C1180 156 1250 156 1280 150L1278 158C1250 162 1180 162 1152 158Z"/>
        <rect x="1160" y="166" width="110" height="5"/>
        <rect x="1172" y="158" width="7" height="58"/>
        <rect x="1251" y="158" width="7" height="58"/>
        <rect x="1211" y="158" width="8" height="10"/>
    </g>
    <path class="lake__ripple" d="M90 204h120M380 226h160M700 207h90M980 229h140M1160 222h110M1300 214h90" stroke-width="1"/>
    <g class="lake__lanterns">
        @for ($i = 0; $i < 43; $i++)
            <rect x="{{ 120 + 24 * $i }}" y="{{ $heights[$i % 7] }}" width="5" height="6" rx="1"/>
        @endfor
    </g>
    <circle class="lake__halo" cx="1372" cy="214" r="13"/>
    <rect class="lake__lantern" x="1368" y="210" width="8" height="9" rx="1.5"/>
</svg>
