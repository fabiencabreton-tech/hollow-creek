<?php

namespace App\Filament\Pages;

use App\Models\ValidationItem;
use App\Services\ValidationDesk;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * File de validation centralisée : fiches, avatars, demandes de famille et
 * inscriptions en attente, avec aperçu complet et décision en une fenêtre.
 */
class ValidationQueue extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = 'Validation';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'À valider';

    protected static ?string $title = 'À valider';

    protected static ?string $slug = 'a-valider';

    protected string $view = 'filament.pages.validation-queue';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->can('personnages.valider') || $user->can('bottin.gerer') || $user->can('comptes.moderer'));
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canAccess()) {
            return null;
        }

        $count = app(ValidationDesk::class)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function table(Table $table): Table
    {
        $desk = app(ValidationDesk::class);

        return $table
            ->query($desk->query())
            ->defaultSort('submitted_at', 'asc')
            ->emptyStateHeading('Rien à valider')
            ->emptyStateDescription('Toutes les demandes ont reçu une réponse.')
            ->columns([
                TextColumn::make('kind')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (ValidationItem $record): string => $record->kindLabel())
                    ->color(fn (string $state): string => match ($state) {
                        'fiche' => 'warning',
                        'famille' => 'info',
                        'avatar' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('item_id')
                    ->label('Demande')
                    ->formatStateUsing(fn (ValidationItem $record): string => $desk->title($record))
                    ->wrap(),
                TextColumn::make('user.pseudo')->label('Membre')->placeholder('Visiteur'),
                TextColumn::make('submitted_at')
                    ->label('Envoyée')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kind')->label('Type')->options(ValidationItem::KINDS),
            ])
            ->recordAction('review')
            ->recordActions([
                Action::make('review')
                    ->label('Examiner')
                    ->icon(Heroicon::OutlinedEye)
                    ->button()
                    ->visible(fn (ValidationItem $record): bool => $desk->canDecide(auth()->user(), $record))
                    ->modalHeading(fn (ValidationItem $record): string => $record->kindLabel().' · '.$desk->title($record))
                    ->modalWidth('5xl')
                    ->modalContent(fn (ValidationItem $record) => view('filament.validation.preview', [
                        'item' => $record,
                        'subject' => $desk->subject($record),
                    ]))
                    ->schema(fn (ValidationItem $record): array => [
                        Radio::make('decision')
                            ->label('Décision')
                            ->options($desk->decisions($record))
                            ->default(ValidationDesk::ACCEPT)
                            ->inline()
                            ->required()
                            ->live(),
                        Textarea::make('reason')
                            ->label(fn (Get $get): string => $get('decision') === ValidationDesk::ACCEPT ? 'Message au membre (facultatif)' : 'Motif (envoyé au membre)')
                            ->required(fn (Get $get): bool => $get('decision') !== ValidationDesk::ACCEPT)
                            ->rows(4)
                            ->maxLength(2000),
                    ])
                    ->modalSubmitActionLabel('Enregistrer la décision')
                    ->action(function (ValidationItem $record, array $data) use ($desk): void {
                        $desk->decide(auth()->user(), $record, $data['decision'], $data['reason'] ?? null);
                        Notification::make()->title('Décision enregistrée')->body('Le membre est prévenu.')->success()->send();
                    }),
                Action::make('quickAccept')
                    ->label('Accepter')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (ValidationItem $record): bool => $desk->canDecide(auth()->user(), $record))
                    ->requiresConfirmation()
                    ->modalHeading(fn (ValidationItem $record): string => 'Accepter : '.$desk->title($record))
                    ->modalDescription('Sans message particulier. Le membre est prévenu.')
                    ->action(function (ValidationItem $record) use ($desk): void {
                        $desk->decide(auth()->user(), $record, ValidationDesk::ACCEPT, null);
                        Notification::make()->title('Accepté')->success()->send();
                    }),
            ])
            ->poll('60s');
    }
}
