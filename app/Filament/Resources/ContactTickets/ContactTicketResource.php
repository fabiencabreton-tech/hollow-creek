<?php

namespace App\Filament\Resources\ContactTickets;

use App\Filament\Resources\ContactTickets\Pages\ListContactTickets;
use App\Filament\Resources\ContactTickets\Pages\ViewContactTicket;
use App\Models\ContactMessage;
use App\Models\ContactTicket;
use App\Models\User;
use App\Services\ContactDesk;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Boîte de réception de la bulle de contact : réponses, notes internes, attribution, clôture.
 */
class ContactTicketResource extends Resource
{
    protected static ?string $model = ContactTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Communauté';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'demande';

    protected static ?string $pluralModelLabel = 'demandes';

    protected static ?string $navigationLabel = 'Demandes de contact';

    protected static ?string $slug = 'demandes';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getNavigationBadge(): ?string
    {
        $open = ContactTicket::query()->where('status', ContactTicket::STATUS_OPEN)->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['assignee', 'user']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Demande')
                ->columnSpanFull()
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    TextEntry::make('reference')->label('Référence'),
                    TextEntry::make('motif')->label('Motif')->formatStateUsing(fn (ContactTicket $record): string => $record->motifLabel()),
                    TextEntry::make('status')->label('Statut')->badge()->formatStateUsing(fn (string $state): string => ContactTicket::STATUS_LABELS[$state] ?? $state),
                    TextEntry::make('name')->label('Nom')->formatStateUsing(fn (ContactTicket $record): string => $record->name.($record->user ? ' (compte '.$record->user->pseudo.')' : ' (sans compte)')),
                    TextEntry::make('email')->label('E-mail'),
                    TextEntry::make('assignee.pseudo')->label('Suivie par')->placeholder('Personne'),
                    TextEntry::make('subject')->label('Sujet')->columnSpanFull(),
                ]),
            Section::make('Échanges')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('thread')
                        ->hiddenLabel()
                        ->state(fn (ContactTicket $record): HtmlString => static::thread($record)),
                ]),
        ]);
    }

    protected static function thread(ContactTicket $record): HtmlString
    {
        $html = $record->messages()->with('user')->get()->map(function (ContactMessage $message) use ($record): string {
            $who = match (true) {
                $message->is_internal => 'Note interne · '.e($message->user?->pseudo ?? 'Équipe'),
                $message->author === 'equipe' => 'Équipe · '.e($message->user?->pseudo ?? '—'),
                default => e($record->name),
            };

            $style = $message->is_internal ? 'border-left:3px solid #d8a657;padding-left:10px;opacity:.85' : 'border-left:3px solid #7a2633;padding-left:10px';

            return '<div style="'.$style.';margin-bottom:14px"><p style="font-size:.85rem;opacity:.75">'.$who.' · '
                .e($message->created_at?->format('d/m/Y H:i') ?? '').'</p><p>'.nl2br(e($message->body), false).'</p></div>';
        })->implode('');

        return new HtmlString($html);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_activity_at', 'desc')
            ->columns([
                TextColumn::make('reference')->label('Réf.')->searchable(),
                TextColumn::make('subject')->label('Sujet')->limit(50)->searchable(),
                TextColumn::make('name')->label('De')->searchable(),
                TextColumn::make('motif')->label('Motif')->formatStateUsing(fn (ContactTicket $record): string => $record->motifLabel()),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (string $state): string => ContactTicket::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        ContactTicket::STATUS_OPEN => 'warning',
                        ContactTicket::STATUS_ANSWERED => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('assignee.pseudo')->label('Suivie par')->placeholder('—'),
                TextColumn::make('last_activity_at')->label('Activité')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(ContactTicket::STATUS_LABELS)->default(ContactTicket::STATUS_OPEN),
            ])
            ->recordActions([
                ViewAction::make()->label('Ouvrir'),
            ]);
    }

    /**
     * @return array<Action>
     */
    public static function deskActions(): array
    {
        return [
            Action::make('reply')
                ->label('Répondre')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('primary')
                ->modalDescription('La personne reçoit un e-mail avec un lien vers la conversation. Écrivez vous-même votre réponse.')
                ->schema([Textarea::make('body')->label('Réponse')->required()->rows(8)->maxLength(5000)])
                ->action(function (ContactTicket $record, array $data): void {
                    abort_unless(auth()->user()?->can('update', $record), 403);
                    app(ContactDesk::class)->staffReply($record, auth()->user(), $data['body']);
                    Notification::make()->title('Réponse envoyée')->success()->send();
                }),
            Action::make('note')
                ->label('Note interne')
                ->icon(Heroicon::OutlinedPencil)
                ->color('gray')
                ->schema([Textarea::make('body')->label('Note (visible de l’équipe seulement)')->required()->maxLength(5000)])
                ->action(function (ContactTicket $record, array $data): void {
                    abort_unless(auth()->user()?->can('update', $record), 403);
                    app(ContactDesk::class)->note($record, auth()->user(), $data['body']);
                    Notification::make()->title('Note ajoutée')->success()->send();
                }),
            Action::make('assign')
                ->label('Attribuer')
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('gray')
                ->fillForm(fn (ContactTicket $record): array => ['assigned_to' => $record->assigned_to ?? auth()->id()])
                ->schema([
                    Select::make('assigned_to')
                        ->label('Suivie par')
                        ->options(fn (): array => User::permission('contact.repondre')->orderBy('pseudo')->pluck('pseudo', 'id')->all())
                        ->required(),
                ])
                ->action(function (ContactTicket $record, array $data): void {
                    abort_unless(auth()->user()?->can('update', $record), 403);
                    $record->forceFill(['assigned_to' => $data['assigned_to']])->save();
                    Notification::make()->title('Demande attribuée')->success()->send();
                }),
            Action::make('close')
                ->label('Clore')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('gray')
                ->visible(fn (ContactTicket $record): bool => $record->status !== ContactTicket::STATUS_CLOSED)
                ->requiresConfirmation()
                ->action(function (ContactTicket $record): void {
                    abort_unless(auth()->user()?->can('update', $record), 403);
                    app(ContactDesk::class)->close($record, auth()->user());
                    Notification::make()->title('Demande close')->success()->send();
                }),
            Action::make('reopen')
                ->label('Rouvrir')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('gray')
                ->visible(fn (ContactTicket $record): bool => $record->status === ContactTicket::STATUS_CLOSED)
                ->action(function (ContactTicket $record): void {
                    abort_unless(auth()->user()?->can('update', $record), 403);
                    app(ContactDesk::class)->reopen($record);
                    Notification::make()->title('Demande rouverte')->success()->send();
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactTickets::route('/'),
            'view' => ViewContactTicket::route('/{record}'),
        ];
    }
}
