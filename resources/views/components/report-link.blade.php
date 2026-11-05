@props(['type', 'id', 'owner' => null])
@auth
    @if ($owner === null || $owner !== auth()->id())
        <a {{ $attributes->class(['report-link']) }} href="{{ route('reports.create', ['type' => $type, 'id' => $id]) }}" rel="nofollow">Signaler</a>
    @endif
@endauth
