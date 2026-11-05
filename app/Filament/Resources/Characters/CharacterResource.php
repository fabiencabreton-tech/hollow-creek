<?php

namespace App\Filament\Resources\Characters;

use App\Enums\CharacterStatus;
use App\Filament\Resources\Characters\Pages\ListCharacters;
use App\Filament\Resources\Characters\Pages\ViewCharacter;
use App\Models\Character;
use App\Models\CharacterReview;
use App\Services\CharacterWorkflow;
use App\Support\ImageStore;
use App\Support\Markdown;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
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
 * Examen des fiches de personnage par l'équipe : lecture, acceptation,
 * demande de modifications, refus, archivage. L'équipe ne modifie jamais
 * le texte d'un joueur : elle commente, le joueur corrige.
 */
class CharacterResource extends Resource
{
    protected static ?string $model = Character::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Personnages';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'fiche';

    protected static ?string $pluralModelLabel = 'fiches';

    protected static ?string $navigationLabel = 'Fiches';

    protected static ?string $slug = 'fiches';

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function getNavigationBadge(): ?string
    {
        $pending = Character::query()->where('status', CharacterStatus::Pending->value)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'species', 'family', 'faceclaim', 'traits', 'kirisawaVictim']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identité')
                ->columnSpanFull()
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        ImageEntry::make('avatar_path')
                            ->label('Avatar')
                            ->disk(ImageStore::DISK)
                            ->visibility('public')
                            ->imageHeight(220)
                            ->placeholder('Aucun avatar'),
                        Grid::make(1)->columnSpan(['md' => 2])->schema([
                            TextEntry::make('full_name')->label('Nom')->state(fn (Character $record): string => $record->fullName()),
                            TextEntry::make('status')->label('Statut')->badge(),
                            TextEntry::make('user.pseudo')->label('Joueur'),
                            TextEntry::make('age')
                                ->label('Âge')
                                ->formatStateUsing(fn (Character $record): string => $record->age.' ans'.($record->apparent_age ? ' (en paraît '.$record->apparent_age.')' : '')),
                            TextEntry::make('species.name')->label('Nature')->placeholder('—'),
                            TextEntry::make('family.name')->label('Famille')->placeholder('Indépendant'),
                            TextEntry::make('profession')->label('Profession')->placeholder('—'),
                            TextEntry::make('faceclaim.name')->label('Célébrité')->placeholder('—'),
                            TextEntry::make('avatar_credit')->label('Crédit photo')->placeholder('—'),
                            TextEntry::make('predefined.title')->label('Prédéfini repris')->placeholder('—'),
                            TextEntry::make('quote')->label('Citation')->placeholder('—'),
                        ]),
                    ]),
                ]),
            Section::make('Nature et capacités')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('traits_list')
                        ->label('Spécialisations et conditions')
                        ->state(fn (Character $record): string => $record->traits->pluck('name')->join(', ') ?: '—'),
                    TextEntry::make('cumul')
                        ->label('À vérifier')
                        ->color('warning')
                        ->state(fn (Character $record): ?string => $record->hasTrait('mediums') && $record->hasTrait('praticiens-de-l-occulte')
                            ? 'Médium et praticien de l’occulte : cumul à justifier.'
                            : null)
                        ->placeholder('—'),
                    TextEntry::make('kirisawa')
                        ->label('Registre de Kirisawa')
                        ->state(fn (Character $record): ?string => $record->kirisawaVictim
                            ? 'N°'.$record->kirisawaVictim->number.' · '.$record->kirisawaVictim->fullName().' ('.$record->kirisawaVictim->status->getLabel().')'
                            : null)
                        ->placeholder('—'),
                    TextEntry::make('death_date')->label('Date de mort')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('death_circumstances')->label('Circonstances de la mort')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('anchor')->label('Attache')->placeholder('—'),
                    TextEntry::make('emotion')->label('Émotion dominante')->placeholder('—'),
                    TextEntry::make('specialties')
                        ->label('Spécialités occultes')
                        ->state(fn (Character $record): ?string => implode(', ', $record->occultSpecialtyLabels()) ?: null)
                        ->placeholder('—'),
                    TextEntry::make('curse_origin')->label('Origine de la malédiction')->placeholder('—'),
                    TextEntry::make('curse_effects')->label('Effets de la malédiction')->placeholder('—'),
                    TextEntry::make('curse_resolution')->label('Rupture possible')->placeholder('—'),
                    TextEntry::make('abilities')->label('Capacités')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('weaknesses')->label('Faiblesses et limites')->placeholder('—')->columnSpanFull(),
                ]),
            Section::make('Histoire')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('history')
                        ->hiddenLabel()
                        ->prose()
                        ->formatStateUsing(fn (?string $state): HtmlString => Markdown::render($state))
                        ->placeholder('Non rédigée'),
                ]),
            Section::make('Caractère')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('personality')
                        ->hiddenLabel()
                        ->prose()
                        ->formatStateUsing(fn (?string $state): HtmlString => Markdown::render($state))
                        ->placeholder('Non rédigé'),
                ]),
            Section::make('Historique des décisions')
                ->columnSpanFull()
                ->collapsible()
                ->schema([
                    TextEntry::make('decisions')
                        ->hiddenLabel()
                        ->state(fn (Character $record): HtmlString => static::reviewHistory($record))
                        ->placeholder('Aucune décision pour le moment'),
                ]),
        ]);
    }

    protected static function reviewHistory(Character $record): HtmlString
    {
        $items = $record->reviews()->with('reviewer')->latest('created_at')->get()->map(
            fn (CharacterReview $review): string => '<li><strong>'.e($review->decision->getLabel()).'</strong> · '
                .e($review->created_at?->format('d/m/Y H:i') ?? '').' · '.e($review->reviewer?->pseudo ?? '—')
                .($review->comment ? '<br>'.nl2br(e($review->comment)) : '').'</li>'
        );

        return new HtmlString($items->isEmpty() ? '' : '<ul>'.$items->implode('').'</ul>');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('first_name')
                    ->label('Personnage')
                    ->formatStateUsing(fn (Character $record): string => $record->fullName())
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('user.pseudo')->label('Joueur')->searchable(),
                TextColumn::make('species.name')->label('Nature')->placeholder('—'),
                TextColumn::make('faceclaim.name')->label('Célébrité')->placeholder('—'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('submitted_at')->label('Envoyée le')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(CharacterStatus::class)
                    ->default(CharacterStatus::Pending->value),
            ])
            ->recordActions([
                ViewAction::make()->label('Lire'),
                ActionGroup::make(static::reviewActions()),
            ]);
    }

    /**
     * @return array<Action>
     */
    public static function reviewActions(): array
    {
        return [
            Action::make('accept')
                ->label('Accepter')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (Character $record): bool => static::canReview($record))
                ->modalHeading('Accepter cette fiche')
                ->modalDescription('Le personnage devient public et jouable ; sa célébrité passe au statut « pris ».')
                ->schema([
                    Textarea::make('comment')->label('Mot pour le joueur (facultatif)')->maxLength(2000),
                ])
                ->action(function (Character $record, array $data): void {
                    abort_unless(static::canReview($record), 403);
                    app(CharacterWorkflow::class)->accept($record, auth()->user(), ($data['comment'] ?? null) ?: null);
                    Notification::make()->title('Fiche acceptée')->success()->send();
                }),
            Action::make('requestChanges')
                ->label('Demander des modifications')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('warning')
                ->visible(fn (Character $record): bool => static::canReview($record))
                ->modalHeading('Demander des modifications')
                ->modalDescription('La fiche redevient modifiable par le joueur, qui reçoit votre commentaire.')
                ->schema([
                    Textarea::make('comment')->label('Ce qu’il faut revoir')->required()->maxLength(2000)->rows(6),
                ])
                ->action(function (Character $record, array $data): void {
                    abort_unless(static::canReview($record), 403);
                    app(CharacterWorkflow::class)->requestChanges($record, auth()->user(), $data['comment']);
                    Notification::make()->title('Modifications demandées')->success()->send();
                }),
            Action::make('refuse')
                ->label('Refuser')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (Character $record): bool => static::canReview($record))
                ->modalHeading('Refuser cette fiche')
                ->modalDescription('La célébrité et le prédéfini éventuel redeviennent disponibles. Le motif est envoyé au joueur.')
                ->schema([
                    Textarea::make('comment')->label('Motif du refus')->required()->maxLength(2000)->rows(5),
                ])
                ->action(function (Character $record, array $data): void {
                    abort_unless(static::canReview($record), 403);
                    app(CharacterWorkflow::class)->refuse($record, auth()->user(), $data['comment']);
                    Notification::make()->title('Fiche refusée')->success()->send();
                }),
            Action::make('archive')
                ->label('Archiver')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->color('gray')
                ->visible(fn (Character $record): bool => $record->status === CharacterStatus::Accepted && (auth()->user()?->can('archive', $record) ?? false))
                ->modalHeading('Archiver ce personnage')
                ->modalDescription('Le personnage n’est plus jouable et sa célébrité redevient disponible.')
                ->schema([
                    Textarea::make('comment')->label('Motif (facultatif)')->maxLength(2000),
                ])
                ->action(function (Character $record, array $data): void {
                    abort_unless($record->status === CharacterStatus::Accepted && auth()->user()?->can('archive', $record), 403);
                    app(CharacterWorkflow::class)->archive($record, auth()->user(), ($data['comment'] ?? null) ?: null);
                    Notification::make()->title('Personnage archivé')->success()->send();
                }),
        ];
    }

    protected static function canReview(Character $record): bool
    {
        return $record->status === CharacterStatus::Pending && (auth()->user()?->can('review', $record) ?? false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCharacters::route('/'),
            'view' => ViewCharacter::route('/{record}'),
        ];
    }
}
