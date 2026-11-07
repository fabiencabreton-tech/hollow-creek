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
            'max_characters' => $settings->maxCharacters(),
            'reservation_days' => $settings->reservationDays(),
            'contact_delay' => $settings->contactDelay(),
            'illustrations_credit' => $settings->illustrationsCredit(),
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
                Section::make('Bulle de contact')
                    ->description('Affiché dans la bulle quand aucun membre de l’équipe n’a été actif depuis cinq minutes.')
                    ->schema([
                        TextInput::make('contact_delay')
                            ->label('Délai de réponse annoncé')
                            ->required()
                            ->maxLength(120),
                    ]),
                Section::make('Illustrations du site')
                    ->description('Mention affichée en pied de page pour les illustrations d’ambiance (bandeaux, accueil, Gazette). Le règlement demande de signaler une illustration générée.')
                    ->schema([
                        TextInput::make('illustrations_credit')
                            ->label('Crédit des illustrations')
                            ->helperText('Laissez vide pour ne rien afficher.')
                            ->maxLength(200),
                    ]),
                Section::make('Personnages')
                    ->description('Le plafond compte les fiches en cours et acceptées ; les fiches refusées ou archivées n’en font pas partie.')
                    ->schema([
                        TextInput::make('max_characters')
                            ->label('Personnages par compte')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(20)
                            ->required(),
                        TextInput::make('reservation_days')
                            ->label('Durée d’une réservation d’avatar (jours)')
                            ->helperText('Prolongeable une fois de la même durée.')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(60)
                            ->required(),
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
            'max_characters' => max(1, min(20, (int) ($data['max_characters'] ?? 3))),
            'reservation_days' => max(1, min(60, (int) ($data['reservation_days'] ?? 7))),
            'contact_delay' => trim((string) ($data['contact_delay'] ?? '')),
            'illustrations_credit' => trim((string) ($data['illustrations_credit'] ?? '')),
        ];

        app(Settings::class)->setMany($values);

        ActivityLogger::record('reglages.modifies', properties: $values);

        Notification::make()->title('Réglages enregistrés')->success()->send();
    }
}
