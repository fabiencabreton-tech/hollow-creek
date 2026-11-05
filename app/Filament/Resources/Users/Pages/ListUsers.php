<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected ?string $subheading = 'Les comptes se créent par l’inscription sur le site. Les comptes administrateurs se créent en console (php artisan hollow:admin).';
}
