<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\ClassifiedAd;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\JournalEntry;
use App\Models\Message;
use App\Models\Post;
use App\Models\Report;
use App\Support\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Signaler un contenu : réponse de scène, message privé, profil, publication, commentaire.
 * Seule la partie signalée est copiée pour l'équipe.
 */
class ReportController extends Controller
{
    public function create(Request $request): View
    {
        [$type, $target] = $this->resolve((string) $request->query('type'), (int) $request->query('id'), $request);

        return view('reports.create', [
            'type' => $type,
            'target' => $target,
            'excerpt' => Str::limit($this->snapshot($type, $target), 400),
            'back' => url()->previous(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Report::TYPES))],
            'id' => ['required', 'integer'],
            'reason' => ['required', Rule::in(array_keys(config('hollow.reports.reasons')))],
            'details' => ['nullable', 'string', 'max:2000'],
        ], [], ['reason' => 'motif', 'details' => 'précisions']);

        [$type, $target] = $this->resolve($data['type'], (int) $data['id'], $request);

        $already = Report::query()
            ->where('reporter_id', $request->user()->getKey())
            ->where('reportable_type', $type)
            ->where('reportable_id', $target->getKey())
            ->where('status', Report::STATUS_OPEN)
            ->exists();

        if (! $already) {
            $report = new Report;
            $report->forceFill([
                'reporter_id' => $request->user()->getKey(),
                'reportable_type' => $type,
                'reportable_id' => $target->getKey(),
                'reported_user_id' => $target->getAttribute('user_id'),
                'snapshot' => $this->snapshot($type, $target),
                'reason' => $data['reason'],
                'details' => $data['details'] ?? null,
                'status' => Report::STATUS_OPEN,
            ])->save();

            ActivityLogger::record('signalement.recu', $report, Report::TYPE_LABELS[$type], causer: $request->user());
        }

        return redirect()->to($this->safeBack($request))->with('status', 'Merci : le signalement est transmis à l’équipe, qui seule en prend connaissance.');
    }

    /**
     * @return array{0: string, 1: Model}
     */
    private function resolve(string $type, int $id, Request $request): array
    {
        $class = Report::TYPES[$type] ?? null;
        abort_if($class === null, 404);

        $target = $class::query()->find($id);
        abort_if($target === null, 404);

        $user = $request->user();
        abort_if($target->getAttribute('user_id') === $user->getKey(), 404);

        $visible = match (true) {
            $target instanceof Post => $target->scene !== null && Gate::allows('view', $target->scene),
            $target instanceof Message => $target->conversation !== null && Gate::allows('view', $target->conversation),
            $target instanceof Character => Gate::allows('view', $target),
            $target instanceof FeedPost, $target instanceof FeedComment, $target instanceof ClassifiedAd => true,
            $target instanceof JournalEntry => $target->isVisibleTo($user),
            default => false,
        };

        abort_unless($visible, 404);

        return [$type, $target];
    }

    private function snapshot(string $type, Model $target): string
    {
        return match (true) {
            $target instanceof Post => $target->character_name." :\n".$target->body,
            $target instanceof Message => ($target->author?->pseudo ?? 'Ancien membre')." :\n".$target->body,
            $target instanceof Character => $target->fullName()."\n".$target->quote."\n\n".Str::limit((string) $target->history, 3000),
            $target instanceof FeedPost => $target->character_name." :\n".$target->body.($target->image_credit ? "\n[image, crédit : ".$target->image_credit.']' : ''),
            $target instanceof FeedComment => $target->character_name." :\n".$target->body,
            $target instanceof ClassifiedAd => $target->character_name.' : '.$target->title."\n".$target->body,
            $target instanceof JournalEntry => ($target->character?->fullName() ?? '')." :\n".$target->body,
            default => '',
        };
    }

    private function safeBack(Request $request): string
    {
        $back = (string) $request->input('back', '');

        return str_starts_with($back, url('/')) ? $back : route('dashboard');
    }
}
