<?php

namespace App\Filament\Resources\TicketComments\Pages;

use App\Filament\Resources\TicketComments\TicketCommentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTicketComments extends ListRecords
{
    protected static string $resource = TicketCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
