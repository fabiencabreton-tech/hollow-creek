@props(['url' => null, 'credit' => null, 'alt' => ''])
@if ($url)
    <figure class="figure">
        <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy" decoding="async">
        @if ($credit)
            <figcaption>Crédit : {{ $credit }}</figcaption>
        @endif
    </figure>
@else
    <div class="figure figure--placeholder" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 3h6"/><path d="M12 3v2"/><path d="M8 7h8l1 2v8l-1 2H8l-1-2V9z"/>
            <path d="M12 10.5c1.2 1.4 1.2 3 0 4.2c-1.2-1.2-1.2-2.8 0-4.2z" fill="#d8a657" stroke="#d8a657"/>
        </svg>
        <p>Illustration à venir</p>
    </div>
@endif
