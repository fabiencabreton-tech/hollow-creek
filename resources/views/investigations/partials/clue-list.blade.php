<ul class="clue-list plain-list">
    @foreach ($clues as $clue)
        <li class="clue">
            <a class="clue__title" href="{{ route('clues.show', [$investigation, $clue]) }}">{{ $clue->title }}</a>
            @if (isset($note))
                <span class="clue__note">{{ $note($clue) }}</span>
            @endif
        </li>
    @endforeach
</ul>
