<?php

namespace App\Filament\Pages;

use App\Support\ActivityLogger;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Réglages du site modifiables sans toucher au code.
 *
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Réglages';

    protected static ?string $title = 'Réglages du site';

    protected static ?string $slug = 'reglages';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reglages.gerer') ?? false;
    }

    public function mount(): void
    {
        $settings = app(Settings::class);

        $this->form->fill([
            'registrations_open' => $settings->registrationsOpen(),
            'rp_date' => $settings->rpDate(),
            'weather' => $settings->weather(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Inscriptions')
                    ->description('Fermées, le formulaire d’inscription l’annonce et refuse toute création de compte. Les comptes existants restent utilisables.')
                    ->schema([
                        Toggle::make('registrations_open')
                            ->label('Inscriptions ouvertes'),
                    ]),
                Section::make('Temps du jeu')
                    ->description('Date et météo affichées sur l’accueil et le tableau de bord. Elles avancent au rythme décidé par l’équipe.')
                    ->schema([
                        TextInput::make('rp_date')
                            ->label('Date RP')
                            ->placeholder('Jeudi 5 novembre 2026')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('weather')
                            ->label('Météo de la ville')
                            ->helperText('Affichée sur l’accueil. Laissez vide pour la masquer.')
                            ->placeholder('Brouillard sur le lac, 4 °C')
                            ->maxLength(80),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Enregistrer')
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        $values = [
            'registrations_open' => (bool) ($data['registrations_open'] ?? false),
            'rp_date' => trim((string) ($data['rp_date'] ?? '')),
            'weather' => trim((string) ($data['weather'] ?? '')),
        ];

        app(Settings::class)->setMany($values);

        ActivityLogger::record('reglages.modifies', properties: $values);

        Notification::make()->title('Réglages enregistrés')->success()->send();
    }
}
