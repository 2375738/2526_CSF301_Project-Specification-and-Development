<?php

namespace App\Filament\Resources\TicketStatusChanges\Pages;

use App\Filament\Resources\TicketStatusChanges\TicketStatusChangeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketStatusChange extends CreateRecord
{
    protected static string $resource = TicketStatusChangeResource::class;
}
