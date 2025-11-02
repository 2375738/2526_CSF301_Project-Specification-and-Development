<?php

namespace App\Filament\Resources\TicketAttachments\Pages;

use App\Filament\Resources\TicketAttachments\TicketAttachmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTicketAttachments extends ListRecords
{
    protected static string $resource = TicketAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
