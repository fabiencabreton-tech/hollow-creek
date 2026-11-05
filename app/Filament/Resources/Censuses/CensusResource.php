<?php

namespace App\Filament\Resources\Censuses;

use App\Filament\Resources\Censuses\Pages\CreateCensus;
use App\Filament\Resources\Censuses\Pages\ListCensuses;
use App\Filament\Resources\Censuses\Pages\ViewCensus;
use App\Models\Census;
use App\Models\Character;
use App\Services\CensusService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Recensements d'activité : lancement, suivi des réponses, archivage des personnages non recensés.
 */
class CensusResource extends Resource
{
    protected static ?string $model = Census::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Personnages';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'recensement';

    protected static ?string $pluralModelLabel = 'recensements';

    protected static ?string $navigationLabel = 'Recensements';

    protected static ?string $slug = 'recensements';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Recensement')
                ->columnSpanFull()
                ->description('À l’enregistrement, chaque joueur ayant un personnage accepté est prévenu.')
                ->schema([
                    TextInput::make('title')->label('Titre')->default('Recensement de '.now()->locale('fr')->isoFormat('MMMM YYYY'))->required()->maxLength(150),
                    Textarea::make('message')->label('Message aux joueurs')->rows(4)->maxLength(2000),
                    DateTimePicker::make('ends_at')->label('Date limite')->default(now()->addDays(14))->required()->after('now')->seconds(false),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Suivi')
                ->columnSpanFull()
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    TextEntry::make('title')->label('Titre'),
                    TextEntry::make('ends_at')->label('Date limite')->dateTime('d/m/Y H:i'),
                    TextEntry::make('closed_at')->label('Clos le')->dateTime('d/m/Y H:i')->placeholder('En cours'),
                    TextEntry::make('responses_count')->label('Personnages recensés')->state(fn (Census $record): int => $record->responses()->count()),
                    TextEntry::make('missing')
                        ->label('Non recensés')
                        ->columnSpanFull()
                        ->state(fn (Census $record): HtmlString => new HtmlString(
                            $record->missingCharacters()->map(fn (Character $character): string => e($character->fullName()).' ('.e($character->user?->pseudo ?? '—').')'.($character->isAbsent() ? ' · absence annoncée' : ''))->implode('<br>') ?: 'Aucun'
                        )),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titre'),
                TextColumn::make('ends_at')->label('Date limite')->dateTime('d/m/Y H:i'),
                TextColumn::make('responses_count')->label('Recensés')->counts('responses'),
                TextColumn::make('closed_at')->label('Clos le')->dateTime('d/m/Y')->placeholder('En cours'),
            ])
            ->recordActions([
                ViewAction::make()->label('Suivre'),
            ]);
    }

    /**
     * @return array<Action>
     */
    public static function closingActions(): array
    {
        return [
            Action::make('archiveMissing')
                ->label('Archiver les non recensés')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->color('danger')
                ->visible(fn (Census $record): bool => $record->closed_at === null && $record->ends_at->isPast())
                ->requiresConfirmation()
                ->modalDescription('Les personnages non recensés sont archivés et leurs avatars libérés. Les absences annoncées sont épargnées. Le recensement est ensuite clos.')
                ->action(function (Census $record): void {
                    abort_unless(auth()->user()?->can('update', $record), 403);
                    $count = app(CensusService::class)->archiveMissing($record, auth()->user());
                    Notification::make()->title($count.' personnage(s) archivé(s)')->success()->send();
                }),
            Action::make('close')
                ->label('Clore sans archiver')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('gray')
                ->visible(fn (Census $record): bool => $record->closed_at === null)
                ->requiresConfirmation()
                ->action(function (Census $record): void {
                    abort_unless(auth()->user()?->can('update', $record), 403);
                    $record->forceFill(['closed_at' => now()])->save();
                    Notification::make()->title('Recensement clos')->success()->send();
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCensuses::route('/'),
            'create' => CreateCensus::route('/nouveau'),
            'view' => ViewCensus::route('/{record}'),
        ];
    }
}
