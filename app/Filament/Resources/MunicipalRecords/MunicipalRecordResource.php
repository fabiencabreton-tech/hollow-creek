<?php

namespace App\Filament\Resources\MunicipalRecords;

use App\Filament\Resources\MunicipalRecords\Pages\CreateMunicipalRecord;
use App\Filament\Resources\MunicipalRecords\Pages\EditMunicipalRecord;
use App\Filament\Resources\MunicipalRecords\Pages\ListMunicipalRecords;
use App\Enums\ArchiveCategory;
use App\Filament\Support\ContentFields;
use App\Models\MunicipalRecord;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

class MunicipalRecordResource extends Resource
{
    protected static ?string $model = MunicipalRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Immersion';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'document';

    protected static ?string $pluralModelLabel = 'documents';

    protected static ?string $navigationLabel = 'Archives municipales';

    protected static ?string $slug = 'archives-municipales';

    public static function publicUrl(MunicipalRecord $record): string
    {
        return route('archives.show', $record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Document')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('title')->label('Titre')->required()->maxLength(180)->columnSpanFull(),
                    Select::make('category')->label('Série')->options(ArchiveCategory::class)->default(ArchiveCategory::Council->value)->required()->native(false),
                    TextInput::make('reference')->label('Cote')->helperText('Facultatif, par exemple « 3 D 12 ».')->maxLength(40),
                    DatePicker::make('document_date')->label('Date du document')->helperText('Date dans l’univers ; sert au classement et au filtre par année.'),
                    Select::make('district_id')->label('Quartier')->relationship('district', 'name')->searchable()->preload(),
                    Select::make('location_id')->label('Lieu')->relationship('location', 'name')->searchable()->preload(),
                    Textarea::make('excerpt')->label('Résumé')->helperText('Affiché dans la liste des archives.')->rows(3)->maxLength(600)->columnSpanFull(),
                    ContentFields::markdown('body', 'Texte du document', 'Uniquement ce que les habitants peuvent consulter : les secrets restent dans les archives secrètes de l’équipe.')->columnSpanFull(),
                ]),
            ContentFields::imageSection('archives')->columnSpanFull(),
            ContentFields::publication()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('document_date', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable()->limit(60),
                TextColumn::make('category')->label('Série')->badge(),
                TextColumn::make('reference')->label('Cote')->placeholder('—'),
                TextColumn::make('document_date')->label('Date')->date('d/m/Y')->placeholder('—')->sortable(),
                IconColumn::make('is_published')->label('Publié')->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Série')->options(ArchiveCategory::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMunicipalRecords::route('/'),
            'create' => CreateMunicipalRecord::route('/nouveau'),
            'edit' => EditMunicipalRecord::route('/{record}/modifier'),
        ];
    }
}
