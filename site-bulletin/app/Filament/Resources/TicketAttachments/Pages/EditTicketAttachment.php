<?php

namespace App\Filament\Resources\TicketAttachments\Pages;

use App\Filament\Resources\TicketAttachments\TicketAttachmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTicketAttachment extends EditRecord
{
    protected static string $resource = TicketAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
