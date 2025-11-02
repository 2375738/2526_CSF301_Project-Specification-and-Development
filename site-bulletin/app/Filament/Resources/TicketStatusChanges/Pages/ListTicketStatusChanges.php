<?php

namespace App\Filament\Resources\TicketStatusChanges\Pages;

use App\Filament\Resources\TicketStatusChanges\TicketStatusChangeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTicketStatusChanges extends ListRecords
{
    protected static string $resource = TicketStatusChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
