<?php

namespace App\Filament\Resources\KirisawaVictims;

use App\Enums\VictimRole;
use App\Enums\VictimStatus;
use App\Filament\Resources\KirisawaVictims\Pages\CreateKirisawaVictim;
use App\Filament\Resources\KirisawaVictims\Pages\EditKirisawaVictim;
use App\Filament\Resources\KirisawaVictims\Pages\ListKirisawaVictims;
use App\Filament\Support\ContentFields;
use App\Models\KirisawaVictim;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Registre des 43 victimes de Kirisawa. Les statuts « en examen » et « incarné »
 * suivent automatiquement les fiches de Revenus.
 */
class KirisawaVictimResource extends Resource
{
    protected static ?string $model = KirisawaVictim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;

    protected static string|UnitEnum|null $navigationGroup = 'Univers';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'victime';

    protected static ?string $pluralModelLabel = 'victimes';

    protected static ?string $navigationLabel = 'Registre de Kirisawa';

    protected static ?string $slug = 'registre-kirisawa';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Victime')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('number')
                        ->label('Numéro')
                        ->integer()
                        ->minValue(1)
                        ->maxValue(KirisawaVictim::TOTAL)
                        ->required()
                        ->unique(ignoreRecord: true),
                    Select::make('role')->label('Statut à l’hôpital')->options(VictimRole::class)->required()->native(false),
                    TextInput::make('first_name')->label('Prénom')->maxLength(60),
                    TextInput::make('last_name')->label('Nom')->maxLength(60),
                    TextInput::make('age_at_death')->label('Âge au décès')->integer()->minValue(0)->maxValue(120),
                    Select::make('status')
                        ->label('Statut dans le jeu')
                        ->helperText('« En examen » et « Incarné » suivent automatiquement la fiche du joueur.')
                        ->options(fn (?KirisawaVictim $record): array => in_array($record?->status, [VictimStatus::Reserved, VictimStatus::Played], true)
                            ? [$record->status->value => $record->status->getLabel()]
                            : [VictimStatus::Administrative->value => VictimStatus::Administrative->getLabel(), VictimStatus::Open->value => VictimStatus::Open->getLabel()])
                        ->disabled(fn (?KirisawaVictim $record): bool => in_array($record?->status, [VictimStatus::Reserved, VictimStatus::Played], true))
                        ->dehydrated(fn (?KirisawaVictim $record): bool => ! in_array($record?->status, [VictimStatus::Reserved, VictimStatus::Played], true))
                        ->default(VictimStatus::Administrative->value)
                        ->required(),
                    Textarea::make('summary')
                        ->label('Présentation publique')
                        ->helperText('Affichée dans le registre public. Rien de secret ici.')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ]),
            Section::make('Notes de l’équipe')
                ->description('Jamais affichées sur le site.')
                ->columnSpanFull()
                ->schema([
                    ContentFields::markdown('admin_notes', 'Notes'),
                ]),
            Section::make('Publication')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_published')->label('Visible dans le registre public'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('number')
            ->columns([
                TextColumn::make('number')->label('N°')->sortable(),
                TextColumn::make('last_name')->label('Nom')->formatStateUsing(fn (KirisawaVictim $record): string => $record->fullName())->searchable(['first_name', 'last_name']),
                TextColumn::make('role')->label('Statut à l’hôpital'),
                TextColumn::make('age_at_death')->label('Âge')->placeholder('—'),
                TextColumn::make('status')->label('Statut dans le jeu')->badge(),
                TextColumn::make('character.first_name')->label('Personnage')->formatStateUsing(fn (KirisawaVictim $record): string => $record->character?->fullName() ?? '—')->placeholder('—'),
                IconColumn::make('is_published')->label('Publié')->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(VictimStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKirisawaVictims::route('/'),
            'create' => CreateKirisawaVictim::route('/nouvelle'),
            'edit' => EditKirisawaVictim::route('/{record}/modifier'),
        ];
    }
}
