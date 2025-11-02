<?php

namespace App\Filament\Resources\TicketStatusChanges\Pages;

use App\Filament\Resources\TicketStatusChanges\TicketStatusChangeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTicketStatusChange extends EditRecord
{
    protected static string $resource = TicketStatusChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
