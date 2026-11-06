<?php

namespace App\Filament\Resources\Investigations;

use App\Filament\Resources\Investigations\Pages\CreateInvestigation;
use App\Filament\Resources\Investigations\Pages\EditInvestigation;
use App\Filament\Resources\Investigations\Pages\ListInvestigations;
use App\Enums\InvestigationStatus;
use App\Filament\Support\ContentFields;
use App\Models\Investigation;
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

class InvestigationResource extends Resource
{
    protected static ?string $model = Investigation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'Immersion';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'enquête';

    protected static ?string $pluralModelLabel = 'enquêtes';

    protected static ?string $navigationLabel = 'Enquêtes';

    protected static ?string $slug = 'enquetes';

    public static function publicUrl(Investigation $record): string
    {
        return route('investigations.show', $record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Enquête')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('title')->label('Titre')->required()->maxLength(180)->columnSpanFull(),
                    Select::make('status')->label('Statut')->options(InvestigationStatus::class)->default(InvestigationStatus::Open->value)->required()->native(false),
                    Select::make('location_id')->label('Lieu')->relationship('location', 'name')->searchable()->preload(),
                    Textarea::make('summary')->label('Résumé public')->rows(3)->maxLength(600)->columnSpanFull(),
                    ContentFields::markdown('body', 'Présentation publique', 'Ce que tout le monde sait. La vérité reste dans les archives secrètes ; les indices se gèrent dans « Indices ».')->columnSpanFull(),
                ]),
            ContentFields::imageSection('enquetes')->columnSpanFull(),
            ContentFields::publication()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Enquête')->searchable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('clues_count')->label('Indices')->counts('clues'),
                IconColumn::make('is_published')->label('Publiée')->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(InvestigationStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvestigations::route('/'),
            'create' => CreateInvestigation::route('/nouveau'),
            'edit' => EditInvestigation::route('/{record}/modifier'),
        ];
    }
}
