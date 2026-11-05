@props(['status'])
@php
    $tone = match ($status->getColor()) {
        'success' => 'badge--ok',
        'warning', 'info' => 'badge--wait',
        default => '',
    };
@endphp
<span {{ $attributes->class(['badge', $tone]) }}>{{ $status->getLabel() }}</span>
