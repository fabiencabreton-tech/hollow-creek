{{-- Sur l'illustration du lac Tsukikage : quarante-trois lanternes jaunes au loin, et la
     quarante-quatrième, blanche, seule entre les piliers du torii. Même cadrage que l'image
     (viewBox 1600 × 900, « slice » comme object-fit: cover), pour rester posées sur l'eau. --}}
@php
    $lanterns = [
        [345, 547, 3.8, 4.6, 10.6],
        [367, 547, 3.8, 4.6, 10.6],
        [413, 575, 5.1, 6.1, 14.3],
        [431, 575, 5.1, 6.1, 14.3],
        [463, 562, 4.5, 5.4, 12.6],
        [488, 576, 5.1, 6.1, 14.3],
        [505, 578, 5.2, 6.2, 14.6],
        [528, 568, 4.8, 5.8, 13.4],
        [549, 559, 4.4, 5.3, 12.3],
        [587, 578, 5.2, 6.2, 14.6],
        [597, 535, 3.2, 3.8, 9.0],
        [624, 545, 3.7, 4.4, 10.4],
        [659, 555, 4.2, 5.0, 11.8],
        [666, 538, 3.4, 4.1, 9.5],
        [693, 535, 3.2, 3.8, 9.0],
        [719, 541, 3.5, 4.2, 9.8],
        [754, 560, 4.4, 5.3, 12.3],
        [776, 532, 3.1, 3.7, 8.7],
        [792, 588, 5.7, 6.8, 16.0],
        [845, 586, 5.6, 6.7, 15.7],
        [859, 567, 4.7, 5.6, 13.2],
        [892, 587, 5.7, 6.8, 16.0],
        [911, 590, 5.8, 7.0, 16.2],
        [935, 578, 5.2, 6.2, 14.6],
        [961, 541, 3.5, 4.2, 9.8],
        [991, 536, 3.3, 4.0, 9.2],
        [1016, 566, 4.7, 5.6, 13.2],
        [1033, 535, 3.2, 3.8, 9.0],
        [1053, 538, 3.4, 4.1, 9.5],
        [1088, 567, 4.7, 5.6, 13.2],
        [1095, 589, 5.8, 7.0, 16.2],
        [1214, 560, 4.4, 5.3, 12.3],
        [1302, 536, 3.3, 4.0, 9.2],
        [1320, 575, 5.1, 6.1, 14.3],
        [1340, 545, 3.7, 4.4, 10.4],
        [1361, 536, 3.3, 4.0, 9.2],
        [1420, 576, 5.1, 6.1, 14.3],
        [1441, 575, 5.1, 6.1, 14.3],
        [1453, 547, 3.8, 4.6, 10.6],
        [1486, 566, 4.7, 5.6, 13.2],
        [1531, 554, 4.1, 4.9, 11.5],
        [1552, 533, 3.1, 3.7, 8.7],
        [1588, 540, 3.5, 4.2, 9.8],
    ];
@endphp
<svg class="lake-lanterns" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
    <defs>
        <radialGradient id="lueur-jaune">
            <stop offset="0" stop-color="#ffd690" stop-opacity="0.75"/>
            <stop offset="0.45" stop-color="#ffc06a" stop-opacity="0.22"/>
            <stop offset="1" stop-color="#ffc06a" stop-opacity="0"/>
        </radialGradient>
        <radialGradient id="lueur-blanche">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.9"/>
            <stop offset="0.4" stop-color="#e8f0ff" stop-opacity="0.3"/>
            <stop offset="1" stop-color="#e8f0ff" stop-opacity="0"/>
        </radialGradient>
        <linearGradient id="brume-lac" x1="0" x2="1" y1="0" y2="0">
            <stop offset="0" stop-color="#d9e0e6" stop-opacity="0"/>
            <stop offset="0.35" stop-color="#d9e0e6" stop-opacity="0.26"/>
            <stop offset="0.7" stop-color="#d9e0e6" stop-opacity="0.18"/>
            <stop offset="1" stop-color="#d9e0e6" stop-opacity="0"/>
        </linearGradient>
    </defs>
    <g class="lake-lanterns__group">
        @foreach ($lanterns as [$x, $y, $w, $h, $r])
            <g class="lake-lanterns__lantern">
                <circle class="lake-lanterns__glow" cx="{{ $x }}" cy="{{ $y }}" r="{{ $r }}" fill="url(#lueur-jaune)"/>
                <rect class="lake-lanterns__paper" x="{{ $x - $w / 2 }}" y="{{ $y - $h }}" width="{{ $w }}" height="{{ $h }}" rx="0.8"/>
                <rect class="lake-lanterns__reflet" x="{{ $x - $w / 2 + 0.6 }}" y="{{ $y + 1.5 }}" width="{{ $w - 1.2 }}" height="{{ $h * 1.6 }}" rx="0.6"/>
            </g>
        @endforeach
    </g>
    <g class="lake-lanterns__lantern lake-lanterns__lantern--blanche">
        <circle class="lake-lanterns__glow" cx="1196" cy="598" r="30" fill="url(#lueur-blanche)"/>
        <rect class="lake-lanterns__paper" x="1192.5" y="589.6" width="7" height="8.4" rx="1"/>
        <rect class="lake-lanterns__reflet" x="1193.4" y="600" width="5.2" height="14" rx="0.8"/>
    </g>
    <rect class="lake-lanterns__mist lake-lanterns__mist--one" x="-300" y="500" width="1300" height="70" fill="url(#brume-lac)"/>
    <rect class="lake-lanterns__mist lake-lanterns__mist--two" x="700" y="540" width="1200" height="52" fill="url(#brume-lac)"/>
</svg>
