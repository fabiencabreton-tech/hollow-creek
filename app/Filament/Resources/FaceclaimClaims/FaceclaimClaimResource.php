<?php

namespace App\Filament\Resources\FaceclaimClaims;

use App\Enums\ClaimStatus;
use App\Filament\Resources\FaceclaimClaims\Pages\ListFaceclaimClaims;
use App\Models\FaceclaimClaim;
use App\Services\FaceclaimRegistry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Demandes de réservation du bottin. Les avatars choisis dans une fiche sont
 * décidés avec la fiche elle-même ; seules les demandes faites depuis le bottin
 * se traitent ici.
 */
class FaceclaimClaimResource extends Resource
{
    protected static ?string $model = FaceclaimClaim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmark;

    protected static string|UnitEnum|null $navigationGroup = 'Personnages';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'réservation';

    protected static ?string $pluralModelLabel = 'réservations';

    protected static ?string $navigationLabel = 'Réservations';

    protected static ?string $slug = 'reservations';

    public static function getNavigationBadge(): ?string
    {
        $pending = FaceclaimClaim::query()
            ->where('status', ClaimStatus::Pending->value)
            ->whereNull('character_id')
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['faceclaim', 'user', 'character', 'decider']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('faceclaim.name')->label('Célébrité')->searchable(),
                TextColumn::make('user.pseudo')->label('Joueur')->placeholder('—')->searchable(),
                TextColumn::make('character_id')
                    ->label('Fiche')
                    ->formatStateUsing(fn (FaceclaimClaim $record): string => $record->character?->fullName() ?? '—')
                    ->placeholder('Demande du bottin'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('expires_at')->label('Échéance')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('created_at')->label('Demandée le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('decider.pseudo')->label('Décidée par')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('note')->label('Note')->placeholder('—')->limit(50)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(ClaimStatus::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('accept')
                        ->label('Accepter la réservation')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->visible(fn (FaceclaimClaim $record): bool => static::isDecidable($record))
                        ->requiresConfirmation()
                        ->modalDescription('La célébrité est réservée pour ce joueur pendant la durée prévue dans les réglages.')
                        ->action(function (FaceclaimClaim $record): void {
                            abort_unless(static::isDecidable($record), 403);
                            app(FaceclaimRegistry::class)->accept($record, auth()->user());
                            Notification::make()->title('Réservation acceptée')->success()->send();
                        }),
                    Action::make('refuse')
                        ->label('Refuser')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->visible(fn (FaceclaimClaim $record): bool => static::isDecidable($record))
                        ->schema([
                            Textarea::make('note')->label('Motif (envoyé au joueur)')->maxLength(500),
                        ])
                        ->action(function (FaceclaimClaim $record, array $data): void {
                            abort_unless(static::isDecidable($record), 403);
                            app(FaceclaimRegistry::class)->refuse($record, auth()->user(), ($data['note'] ?? null) ?: null);
                            Notification::make()->title('Demande refusée')->success()->send();
                        }),
                    Action::make('release')
                        ->label('Libérer la célébrité')
                        ->icon(Heroicon::OutlinedLockOpen)
                        ->color('gray')
                        ->visible(fn (FaceclaimClaim $record): bool => static::isReleasable($record))
                        ->schema([
                            Textarea::make('note')->label('Motif')->required()->maxLength(500),
                        ])
                        ->action(function (FaceclaimClaim $record, array $data): void {
                            abort_unless(static::isReleasable($record), 403);
                            app(FaceclaimRegistry::class)->release($record, auth()->user(), $data['note']);
                            Notification::make()->title('Célébrité libérée')->success()->send();
                        }),
                ]),
            ]);
    }

    protected static function canManage(): bool
    {
        return auth()->user()?->can('bottin.gerer') ?? false;
    }

    protected static function isDecidable(FaceclaimClaim $record): bool
    {
        return static::canManage() && $record->status === ClaimStatus::Pending && $record->character_id === null;
    }

    /**
     * Une réservation du bottin peut être libérée ; un avatar de fiche suit la fiche.
     */
    protected static function isReleasable(FaceclaimClaim $record): bool
    {
        return static::canManage() && $record->status->isActive() && $record->character_id === null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaceclaimClaims::route('/'),
        ];
    }
}
