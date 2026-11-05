<?php

namespace App\Filament\Resources\Reports;

use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\Reports\Pages\ViewReport;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\Message;
use App\Models\Post;
use App\Models\Report;
use App\Models\Scene;
use App\Support\ActivityLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * File des signalements. L'équipe ne voit que l'extrait copié au moment du signalement.
 */
class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Communauté';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'signalement';

    protected static ?string $pluralModelLabel = 'signalements';

    protected static ?string $navigationLabel = 'Signalements';

    protected static ?string $slug = 'signalements';

    public static function getNavigationBadge(): ?string
    {
        $open = Report::query()->where('status', Report::STATUS_OPEN)->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['reporter', 'reportedUser', 'handler']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Signalement')
                ->columnSpanFull()
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    TextEntry::make('reportable_type')->label('Contenu')->formatStateUsing(fn (string $state): string => Report::TYPE_LABELS[$state] ?? $state),
                    TextEntry::make('reason')->label('Motif')->formatStateUsing(fn (Report $record): string => $record->reasonLabel()),
                    TextEntry::make('status')->label('Statut')->badge()->formatStateUsing(fn (string $state): string => Report::STATUS_LABELS[$state] ?? $state),
                    TextEntry::make('reporter.pseudo')->label('Signalé par')->placeholder('—'),
                    TextEntry::make('reportedUser.pseudo')->label('Compte concerné')->placeholder('—'),
                    TextEntry::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i'),
                    TextEntry::make('details')->label('Précisions')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('snapshot')->label('Extrait signalé')->columnSpanFull()
                        ->formatStateUsing(fn (string $state) => new \Illuminate\Support\HtmlString(nl2br(e($state), false))),
                    TextEntry::make('resolution')->label('Décision')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('handler.pseudo')->label('Traité par')->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reportable_type')->label('Contenu')->formatStateUsing(fn (string $state): string => Report::TYPE_LABELS[$state] ?? $state),
                TextColumn::make('reason')->label('Motif')->formatStateUsing(fn (Report $record): string => $record->reasonLabel()),
                TextColumn::make('reportedUser.pseudo')->label('Compte concerné')->placeholder('—')->searchable(),
                TextColumn::make('reporter.pseudo')->label('Signalé par')->placeholder('—'),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (string $state): string => Report::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => $state === Report::STATUS_OPEN ? 'danger' : 'gray'),
                TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(Report::STATUS_LABELS)->default(Report::STATUS_OPEN),
            ])
            ->recordActions([
                ViewAction::make()->label('Examiner'),
            ]);
    }

    /**
     * @return array<Action>
     */
    public static function decisionActions(): array
    {
        return [
            Action::make('hide')
                ->label('Masquer le contenu')
                ->icon(Heroicon::OutlinedEyeSlash)
                ->color('danger')
                ->visible(fn (Report $record): bool => static::hideable($record) !== null)
                ->requiresConfirmation()
                ->modalDescription('Le contenu disparaît du site ; il reste restaurable par l’équipe.')
                ->action(function (Report $record): void {
                    abort_unless(auth()->user()?->can('update', $record), 403);
                    $target = static::hideable($record);

                    if ($target === null) {
                        return;
                    }

                    $target->delete();

                    if ($target instanceof Post && ($scene = Scene::withTrashed()->find($target->scene_id))) {
                        $scene->forceFill([
                            'posts_count' => max(0, $scene->posts_count - 1),
                            'words_count' => max(0, $scene->words_count - $target->word_count),
                        ])->save();
                    }

                    if ($target instanceof FeedComment) {
                        FeedPost::withTrashed()->whereKey($target->feed_post_id)->where('comments_count', '>', 0)->decrement('comments_count');
                    }

                    ActivityLogger::record('signalement.contenu-masque', $record, Report::TYPE_LABELS[$record->reportable_type] ?? '', causer: auth()->user());
                    Notification::make()->title('Contenu masqué')->success()->send();
                }),
            Action::make('handle')
                ->label('Marquer comme traité')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (Report $record): bool => $record->status === Report::STATUS_OPEN)
                ->schema([Textarea::make('resolution')->label('Décision prise')->required()->maxLength(2000)])
                ->action(fn (Report $record, array $data) => static::close($record, Report::STATUS_HANDLED, $data['resolution'])),
            Action::make('dismiss')
                ->label('Classer sans suite')
                ->icon(Heroicon::OutlinedXMark)
                ->color('gray')
                ->visible(fn (Report $record): bool => $record->status === Report::STATUS_OPEN)
                ->schema([Textarea::make('resolution')->label('Raison (facultatif)')->maxLength(2000)])
                ->action(fn (Report $record, array $data) => static::close($record, Report::STATUS_DISMISSED, $data['resolution'] ?? null)),
        ];
    }

    protected static function close(Report $record, string $status, ?string $resolution): void
    {
        abort_unless(auth()->user()?->can('update', $record), 403);

        $record->forceFill([
            'status' => $status,
            'resolution' => $resolution,
            'handled_by' => auth()->id(),
            'handled_at' => now(),
        ])->save();

        ActivityLogger::record('signalement.'.$status, $record, Report::STATUS_LABELS[$status], causer: auth()->user());
        Notification::make()->title('Signalement '.mb_strtolower(Report::STATUS_LABELS[$status]))->success()->send();
    }

    /**
     * Contenu qui peut être masqué (réponse, message, publication, commentaire), s'il est encore visible.
     */
    protected static function hideable(Report $record): ?Model
    {
        $target = $record->target();

        if (! ($target instanceof Post || $target instanceof Message || $target instanceof FeedPost || $target instanceof FeedComment)) {
            return null;
        }

        return $target->trashed() ? null : $target;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'view' => ViewReport::route('/{record}'),
        ];
    }
}
