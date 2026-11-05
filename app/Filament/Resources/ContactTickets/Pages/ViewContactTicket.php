<?php

namespace App\Filament\Resources\ContactTickets\Pages;

use App\Filament\Resources\ContactTickets\ContactTicketResource;
use Filament\Resources\Pages\ViewRecord;

class ViewContactTicket extends ViewRecord
{
    protected static string $resource = ContactTicketResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->reference.' · '.$this->getRecord()->subject;
    }

    protected function getHeaderActions(): array
    {
        return ContactTicketResource::deskActions();
    }
}
