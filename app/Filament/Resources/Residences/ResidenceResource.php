<?php

namespace App\Filament\Resources\Residences;

use App\Filament\Resources\Residences\Pages\CreateResidence;
use App\Filament\Resources\Residences\Pages\EditResidence;
use App\Filament\Resources\Residences\Pages\ListResidences;
use App\Enums\ResidenceKind;
use App\Filament\Support\ContentFields;
use App\Models\Character;
use App\Models\Residence;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ResidenceResource extends Resource
{
    protected static ?string $model = Residence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|UnitEnum|null $navigationGroup = 'Immersion';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'habitation';

    protected static ?string $pluralModelLabel = 'habitations';

    protected static ?string $navigationLabel = 'Habitations';

    protected static ?string $slug = 'habitations';

    public static function publicUrl(Residence $record): string
    {
        return route('residences.show', $record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Habitation')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Nom ou adresse')->helperText('Par exemple « Maison Fujii » ou « Résidence Kohan, appartement 4 ».')->required()->maxLength(150)->columnSpanFull(),
                    Select::make('kind')->label('Type')->options(ResidenceKind::class)->default(ResidenceKind::House->value)->required()->native(false),
                    Select::make('district_id')->label('Quartier')->relationship('district', 'name')->searchable()->preload(),
                    TextInput::make('capacity')->label('Nombre de places')->integer()->minValue(1)->maxValue(20)->default(2)->required(),
                    Toggle::make('is_open')->label('Libre : les joueurs peuvent y emménager seuls')->default(true),
                    Textarea::make('summary')->label('Résumé')->rows(2)->maxLength(400)->columnSpanFull(),
                    ContentFields::markdown('description', 'Description')->columnSpanFull(),
                ]),
            Section::make('Habitants')
                ->columnSpanFull()
                ->visibleOn('edit')
                ->schema([
                    TextEntry::make('residents_list')
                        ->label('Personnages qui habitent ici')
                        ->state(fn (?\App\Models\Residence $record): string => $record?->characters()->orderBy('first_name')->get()->map(fn (Character $character): string => $character->fullName())->join(', ') ?: 'Personne'),
                ]),
            ContentFields::imageSection('univers')->columnSpanFull(),
            ContentFields::publication()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Habitation')->searchable(),
                TextColumn::make('kind')->label('Type'),
                TextColumn::make('district.name')->label('Quartier')->placeholder('—'),
                TextColumn::make('characters_count')->label('Habitants')->counts('characters')->formatStateUsing(fn (int $state, \App\Models\Residence $record): string => $state.' / '.$record->capacity),
                IconColumn::make('is_open')->label('Libre')->boolean(),
                IconColumn::make('is_published')->label('Publiée')->boolean(),
            ])
            ->filters([
                SelectFilter::make('district_id')->label('Quartier')->relationship('district', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResidences::route('/'),
            'create' => CreateResidence::route('/nouveau'),
            'edit' => EditResidence::route('/{record}/modifier'),
        ];
    }
}
