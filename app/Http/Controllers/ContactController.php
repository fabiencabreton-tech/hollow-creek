<?php

namespace App\Http\Controllers;

use App\Models\ContactTicket;
use App\Services\ContactDesk;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Bulle de contact : formulaire, suivi par lien secret, « Mes demandes ».
 * Les réponses viennent toujours d'une personne de l'équipe.
 */
class ContactController extends Controller
{
    public function __construct(private readonly ContactDesk $desk) {}

    public function create(Request $request): View
    {
        $motif = (string) $request->query('motif', 'question');

        return view('contact.create', [
            'motif' => array_key_exists($motif, config('hollow.contact.motifs')) ? $motif : 'question',
            'motifs' => config('hollow.contact.motifs'),
            'about' => mb_substr((string) $request->query('sujet', ''), 0, 150),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => [Rule::requiredIf($user === null), 'nullable', 'string', 'max:60'],
            'email' => [Rule::requiredIf($user === null), 'nullable', 'email', 'max:190'],
            'motif' => ['required', Rule::in(array_keys(config('hollow.contact.motifs')))],
            'subject' => ['required', 'string', 'min:3', 'max:150'],
            'body' => ['required', 'string', 'min:5', 'max:5000'],
        ], [], [
            'name' => 'nom ou pseudo',
            'email' => 'adresse e-mail',
            'motif' => 'motif',
            'subject' => 'sujet',
            'body' => 'message',
        ]);

        $ticket = $this->desk->open([
            'name' => (string) ($data['name'] ?? ''),
            'email' => (string) ($data['email'] ?? ''),
            'motif' => $data['motif'],
            'subject' => $data['subject'],
            'body' => $data['body'],
        ], $user);

        if ($user) {
            return redirect()->route('contact.mine.show', $ticket)->with('status', 'Demande '.$ticket->reference.' envoyée. L’équipe vous répondra ici et par e-mail.');
        }

        return redirect()->route('contact.sent')->with('reference', $ticket->reference);
    }

    public function sent(Request $request): View
    {
        return view('contact.sent', ['reference' => session('reference')]);
    }

    public function track(string $reference, string $token): View
    {
        $ticket = $this->ticketForToken($reference, $token);

        if ($ticket === null) {
            return view('contact.expired');
        }

        return view('contact.track', [
            'ticket' => $ticket->load('publicMessages'),
            'replyUrl' => route('contact.track.reply', ['reference' => $reference, 'token' => $token]),
        ]);
    }

    public function trackReply(Request $request, string $reference, string $token): RedirectResponse
    {
        $ticket = $this->ticketForToken($reference, $token);
        abort_if($ticket === null, 404);

        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:5000']], [], ['body' => 'message']);

        $this->desk->visitorReply($ticket, $request->user(), $data['body']);

        return redirect()->route('contact.track', ['reference' => $reference, 'token' => $token])->with('status', 'Message envoyé à l’équipe.');
    }

    public function resendForm(): View
    {
        return view('contact.resend');
    }

    public function resend(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:12'],
            'email' => ['required', 'email', 'max:190'],
        ], [], ['reference' => 'référence', 'email' => 'adresse e-mail']);

        $this->desk->resendLink($data['reference'], $data['email']);

        // Même réponse dans tous les cas : on ne révèle pas si la demande existe.
        return redirect()->route('contact.resend')->with('status', 'Si cette référence correspond à cette adresse, un nouveau lien vient de partir par e-mail.');
    }

    public function mine(Request $request): View
    {
        return view('contact.mine', [
            'tickets' => ContactTicket::query()->where('user_id', $request->user()->getKey())->latest('last_activity_at')->get(),
        ]);
    }

    public function mineShow(Request $request, ContactTicket $ticket): View
    {
        abort_unless($ticket->user_id === $request->user()->getKey(), 404);

        return view('contact.track', [
            'ticket' => $ticket->load('publicMessages'),
            'replyUrl' => route('contact.mine.reply', $ticket),
        ]);
    }

    public function mineReply(Request $request, ContactTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->getKey(), 404);

        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:5000']], [], ['body' => 'message']);

        $this->desk->visitorReply($ticket, $request->user(), $data['body']);

        return redirect()->route('contact.mine.show', $ticket)->with('status', 'Message envoyé à l’équipe.');
    }

    private function ticketForToken(string $reference, string $token): ?ContactTicket
    {
        $ticket = ContactTicket::query()->where('reference', mb_strtoupper($reference))->first();

        return $ticket && $ticket->tokenMatches($token) ? $ticket : null;
    }
}
