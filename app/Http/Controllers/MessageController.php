<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Services\Messenger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Messagerie entre joueurs.
 */
class MessageController extends Controller
{
    public function __construct(private readonly Messenger $messenger) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $archived = $request->boolean('archives');
        $search = trim((string) $request->query('q', ''));

        $participations = ConversationParticipant::query()
            ->with(['conversation.participants.user'])
            ->where('user_id', $user->getKey())
            ->whereNull('left_at')
            ->when($archived, fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $query->whereHas('conversation', fn ($conversation) => $conversation
                    ->where('subject', 'like', $like)
                    ->orWhereHas('messages', fn ($messages) => $messages->where('body', 'like', $like))
                    ->orWhereHas('participants.user', fn ($users) => $users->where('pseudo', 'like', $like)));
            })
            ->get()
            ->sortByDesc(fn (ConversationParticipant $participant) => $participant->conversation?->last_message_at?->getTimestamp() ?? 0)
            ->values();

        $lastIds = Message::query()
            ->whereIn('conversation_id', $participations->pluck('conversation_id'))
            ->selectRaw('conversation_id, max(id) as last_id')
            ->groupBy('conversation_id')
            ->pluck('last_id', 'conversation_id');

        return view('messages.index', [
            'participations' => $participations,
            'lastIds' => $lastIds,
            'archived' => $archived,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        return view('messages.create', [
            'recipients' => (string) $request->query('a', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipients' => ['required', 'string', 'max:300'],
            'subject' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
        ], [], [
            'recipients' => 'destinataires',
            'subject' => 'sujet',
            'body' => 'message',
        ]);

        $conversation = $this->messenger->start(
            $request->user(),
            preg_split('/[,;]+/', $data['recipients']) ?: [],
            $data['subject'] ?? null,
            $data['body'],
        );

        return redirect()->route('messages.show', $conversation)->with('status', 'Message envoyé.');
    }

    public function show(Request $request, Conversation $conversation): View
    {
        abort_unless(Gate::allows('view', $conversation), 404);

        $conversation->load('participants.user');
        $messages = Message::query()->where('conversation_id', $conversation->getKey())->with('author')->orderByDesc('id')->limit(200)->get()->reverse()->values();
        $this->messenger->markRead($conversation, $request->user());

        return view('messages.show', [
            'conversation' => $conversation,
            'messages' => $messages,
            'participant' => $conversation->participantFor($request->user()),
            'canSend' => Gate::allows('send', $conversation),
        ]);
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse
    {
        abort_unless(Gate::allows('view', $conversation), 404);
        Gate::authorize('send', $conversation);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']], [], ['body' => 'message']);

        $message = $this->messenger->send($conversation, $request->user(), $data['body']);

        return redirect()->to(route('messages.show', $conversation).'#message-'.$message->getKey());
    }

    /**
     * Nouveaux messages depuis le dernier affiché (rafraîchissement toutes les 15 secondes).
     */
    public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless(Gate::allows('view', $conversation), 404);

        $after = max(0, (int) $request->query('apres', 0));
        $messages = Message::query()
            ->where('conversation_id', $conversation->getKey())
            ->where('id', '>', $after)
            ->with('author')
            ->orderBy('id')
            ->limit(50)
            ->get();

        if ($messages->isNotEmpty()) {
            $this->messenger->markRead($conversation, $request->user());
        }

        return response()->json([
            'last_id' => $messages->max('id') ?? $after,
            'html' => $messages->map(fn (Message $message) => view('messages.partials.message', ['message' => $message, 'viewer' => $request->user()])->render())->implode(''),
        ]);
    }

    public function archive(Request $request, Conversation $conversation): RedirectResponse
    {
        abort_unless(Gate::allows('view', $conversation), 404);

        $archived = $this->messenger->toggleArchive($conversation, $request->user());

        return redirect()->route('messages.index')->with('status', $archived ? 'Conversation archivée.' : 'Conversation sortie des archives.');
    }

    public function leave(Request $request, Conversation $conversation): RedirectResponse
    {
        abort_unless(Gate::allows('view', $conversation), 404);

        $this->messenger->leave($conversation, $request->user());

        return redirect()->route('messages.index')->with('status', 'Vous avez quitté la conversation.');
    }
}
