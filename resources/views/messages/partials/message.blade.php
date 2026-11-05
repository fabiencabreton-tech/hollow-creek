<article id="message-{{ $message->id }}" @class(['message', 'message--mine' => $message->user_id === $viewer->id]) data-message-id="{{ $message->id }}">
    <p class="message__meta">
        <strong>{{ $message->author?->pseudo ?? 'Ancien membre' }}</strong>
        · <time datetime="{{ $message->created_at?->toIso8601String() }}">{{ $message->created_at?->locale('fr')->isoFormat('LLL') }}</time>
        <x-report-link type="message" :id="$message->id" :owner="$message->user_id" />
    </p>
    <div class="message__body">{{ $message->html() }}</div>
</article>
