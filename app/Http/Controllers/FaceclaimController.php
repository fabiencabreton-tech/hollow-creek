<?php

namespace App\Http\Controllers;

use App\Enums\ClaimStatus;
use App\Models\Faceclaim;
use App\Models\FaceclaimClaim;
use App\Notifications\GuestClaimConfirmation;
use App\Services\FaceclaimRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

/**
 * Bottin des avatars : qui représente qui, et demandes de réservation.
 */
class FaceclaimController extends Controller
{
    public function __construct(private readonly FaceclaimRegistry $registry) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $key = Faceclaim::key($search);

        $faceclaims = Faceclaim::query()
            ->whereHas('claims', fn ($query) => $query->whereIn('status', ClaimStatus::ACTIVE))
            ->when($key !== '', fn ($query) => $query->where('name_key', 'like', '%'.str_replace(['%', '_'], '', $key).'%'))
            ->with(['activeClaim.character'])
            ->orderBy('name_key')
            ->paginate(60)
            ->withQueryString();

        $exactMatch = $key !== '' ? Faceclaim::query()->where('name_key', $key)->with('activeClaim')->first() : null;

        $user = $request->user();

        return view('faceclaims.index', [
            'faceclaims' => $faceclaims,
            'search' => $search,
            'exactMatch' => $exactMatch,
            'myClaims' => $user ? FaceclaimClaim::query()
                ->with('faceclaim')
                ->where('user_id', $user->getKey())
                ->whereNull('character_id')
                ->whereIn('status', [ClaimStatus::Pending->value, ClaimStatus::Reserved->value])
                ->latest()
                ->get() : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'min:3', 'max:120']], [], ['name' => 'nom de la célébrité']);

        $claim = $this->registry->requestReservation($request->user(), (string) $request->input('name'));

        return redirect()->route('faceclaims.index')->with('status', "Demande enregistrée pour {$claim->faceclaim->name}. L’équipe vous répondra par e-mail.");
    }

    public function extend(Request $request, FaceclaimClaim $claim): RedirectResponse
    {
        Gate::authorize('manageOwn', $claim);

        $this->registry->extend($claim);

        return redirect()->route('faceclaims.index')->with('status', 'Réservation prolongée jusqu’au '.$claim->fresh()->expires_at->locale('fr')->isoFormat('LL').'.');
    }

    public function destroy(Request $request, FaceclaimClaim $claim): RedirectResponse
    {
        Gate::authorize('manageOwn', $claim);

        $this->registry->release($claim, $request->user(), 'Annulée par le joueur');

        return redirect()->route('faceclaims.index')->with('status', 'Demande annulée : la célébrité redevient disponible.');
    }

    /**
     * Demande d'avatar sans compte : confirmée par un lien envoyé par e-mail.
     */
    public function storeGuest(Request $request): RedirectResponse
    {
        abort_if($request->user() !== null, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'guest_pseudo' => ['required', 'string', 'min:2', 'max:30'],
            'guest_email' => ['required', 'email', 'max:190'],
        ], [], [
            'name' => 'nom de la célébrité',
            'guest_pseudo' => 'pseudo',
            'guest_email' => 'adresse e-mail',
        ]);

        [$claim, $token] = $this->registry->requestAsGuest($data['name'], $data['guest_pseudo'], $data['guest_email']);

        Notification::route('mail', $claim->guest_email)
            ->notify(new GuestClaimConfirmation($claim, route('faceclaims.confirm', ['claim' => $claim, 'token' => $token])));

        return redirect()->route('faceclaims.index')->with('status', 'Presque fini : confirmez votre demande grâce au lien envoyé à '.$claim->guest_email.' (valable 48 heures).');
    }

    public function confirmGuest(FaceclaimClaim $claim, string $token): RedirectResponse
    {
        $this->registry->confirmGuest($claim, $token);

        return redirect()->route('faceclaims.index')->with('status', 'Demande confirmée : l’équipe vous répondra par e-mail. Inscrivez-vous avec la même adresse pour retrouver la réservation sur votre compte.');
    }
}
