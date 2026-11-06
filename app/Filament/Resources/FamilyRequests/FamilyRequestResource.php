<?php

namespace App\Filament\Resources\FamilyRequests;

use App\Enums\FamilyRequestStatus;
use App\Filament\Resources\FamilyRequests\Pages\ListFamilyRequests;
use App\Models\FamilyRequest;
use App\Services\FamilyService;
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
 * Demandes d'intégration aux familles et propositions de nouvelles familles.
 */
class FamilyRequestResource extends Resource
{
    protected static ?string $model = FamilyRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Univers';

    protected static ?int $navigationSort = 21;

    protected static ?string $modelLabel = 'demande de famille';

    protected static ?string $pluralModelLabel = 'demandes de famille';

    protected static ?string $navigationLabel = 'Demandes de famille';

    protected static ?string $slug = 'demandes-de-famille';

    public static function getNavigationBadge(): ?string
    {
        $count = FamilyRequest::query()->where('status', FamilyRequestStatus::Pending->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['character', 'family', 'user']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('label')->label('Demande')->getStateUsing(fn (FamilyRequest $record): string => $record->label())->wrap(),
                TextColumn::make('character.first_name')->label('Personnage')->formatStateUsing(fn (FamilyRequest $record): string => $record->character?->fullName() ?? '—'),
                TextColumn::make('user.pseudo')->label('Joueur')->placeholder('—'),
                TextColumn::make('message')->label('Message')->limit(80)->placeholder('—')->wrap(),
                TextColumn::make('proposed_summary')->label('Présentation proposée')->limit(80)->placeholder('—')->toggleable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('created_at')->label('Envoyée le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(FamilyRequestStatus::class)->default(FamilyRequestStatus::Pending->value),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('accept')
                        ->label('Accepter')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->visible(fn (FamilyRequest $record): bool => $record->status === FamilyRequestStatus::Pending)
                        ->modalDescription(fn (FamilyRequest $record): string => $record->isProposal()
                            ? 'La famille « '.$record->proposed_name.' » sera créée et publiée, avec ce personnage pour premier membre. Vous pourrez compléter sa fiche ensuite.'
                            : 'Le personnage rejoint la famille et apparaît dans son arbre.')
                        ->schema([
                            Textarea::make('response')->label('Message au joueur (facultatif)')->maxLength(1000),
                        ])
                        ->action(function (FamilyRequest $record, array $data): void {
                            abort_unless(auth()->user()->can('update', $record), 403);
                            $family = app(FamilyService::class)->approve($record, auth()->user(), ($data['response'] ?? null) ?: null);
                            Notification::make()->title('Demande acceptée : '.$family->name)->success()->send();
                        }),
                    Action::make('refuse')
                        ->label('Refuser')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->visible(fn (FamilyRequest $record): bool => $record->status === FamilyRequestStatus::Pending)
                        ->schema([
                            Textarea::make('response')->label('Motif (envoyé au joueur)')->required()->maxLength(1000),
                        ])
                        ->action(function (FamilyRequest $record, array $data): void {
                            abort_unless(auth()->user()->can('update', $record), 403);
                            app(FamilyService::class)->refuse($record, auth()->user(), $data['response']);
                            Notification::make()->title('Demande refusée')->success()->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFamilyRequests::route('/'),
        ];
    }
}
