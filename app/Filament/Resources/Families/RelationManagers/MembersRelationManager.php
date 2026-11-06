<?php

namespace App\Filament\Resources\Families\RelationManagers;

use App\Enums\CharacterStatus;
use App\Models\Character;
use App\Models\FamilyMember;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Arbre généalogique : personnages joués et parents non joués (ancêtres, disparus).
 */
class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'treeMembers';

    protected static ?string $title = 'Arbre généalogique';

    protected static ?string $modelLabel = 'membre';

    protected static ?string $pluralModelLabel = 'membres';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        $familyId = $this->getOwnerRecord()->getKey();
        $nodes = fn (?FamilyMember $record): array => FamilyMember::query()
            ->where('family_id', $familyId)
            ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
            ->with('character')
            ->get()
            ->mapWithKeys(fn (FamilyMember $member): array => [$member->getKey() => $member->displayName()])
            ->all();

        return $schema->columns(2)->components([
            Select::make('character_id')
                ->label('Personnage joué')
                ->helperText('Laisser vide pour un parent non joué (ancêtre, membre disparu). Le personnage doit appartenir à la famille.')
                ->options(fn (): array => Character::query()
                    ->where('family_id', $familyId)
                    ->where('status', CharacterStatus::Accepted->value)
                    ->orderBy('first_name')
                    ->get()
                    ->mapWithKeys(fn (Character $character): array => [$character->getKey() => $character->fullName()])
                    ->all())
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state, callable $set): void {
                    if ($state && $character = Character::query()->find($state)) {
                        $set('name', $character->fullName());
                    }
                }),
            TextInput::make('name')->label('Nom affiché')->required()->maxLength(120),
            Select::make('parent_id')->label('Parent')->options(fn (?FamilyMember $record): array => $nodes($record))->searchable(),
            Select::make('second_parent_id')->label('Second parent')->options(fn (?FamilyMember $record): array => $nodes($record))->searchable(),
            Select::make('spouse_id')->label('Conjoint ou conjointe')->options(fn (?FamilyMember $record): array => $nodes($record))->searchable(),
            TextInput::make('note')->label('Mention publique')->maxLength(300),
            Toggle::make('is_deceased')->label('Décédé·e'),
            TextInput::make('sort_order')->label('Ordre parmi les frères et sœurs')->integer()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['character', 'parent', 'spouse']))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Membre')->formatStateUsing(fn (FamilyMember $record): string => $record->displayName())->searchable(),
                IconColumn::make('character_id')->label('Joué')->boolean()->getStateUsing(fn (FamilyMember $record): bool => $record->character_id !== null),
                TextColumn::make('parent.name')->label('Parent')->placeholder('—'),
                TextColumn::make('spouse.name')->label('Conjoint')->placeholder('—'),
                IconColumn::make('is_deceased')->label('Décédé·e')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->mutateDataUsing(fn (array $data): array => $this->checked($data, null)),
            ])
            ->recordActions([
                EditAction::make()->mutateDataUsing(fn (array $data, FamilyMember $record): array => $this->checked($data, $record)),
                DeleteAction::make(),
            ]);
    }

    /**
     * Pas de boucle dans l'arbre, et un personnage n'apparaît qu'une fois.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function checked(array $data, ?FamilyMember $record): array
    {
        $forbidden = $record ? $record->descendantIds() : [];

        foreach (['parent_id', 'second_parent_id'] as $field) {
            if (! empty($data[$field]) && in_array((int) $data[$field], $forbidden, true)) {
                throw ValidationException::withMessages(["mountedActions.0.data.{$field}" => 'Un descendant ne peut pas être aussi un parent.']);
            }
        }

        if (! empty($data['character_id']) && FamilyMember::query()->where('character_id', $data['character_id'])->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))->exists()) {
            throw ValidationException::withMessages(['mountedActions.0.data.character_id' => 'Ce personnage figure déjà dans un arbre.']);
        }

        return $data;
    }
}
