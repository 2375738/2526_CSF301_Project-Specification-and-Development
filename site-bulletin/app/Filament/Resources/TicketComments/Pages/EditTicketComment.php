<?php

namespace App\Filament\Resources\TicketComments\Pages;

use App\Filament\Resources\TicketComments\TicketCommentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTicketComment extends EditRecord
{
    protected static string $resource = TicketCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
