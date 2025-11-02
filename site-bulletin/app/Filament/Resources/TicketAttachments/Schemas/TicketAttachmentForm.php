<?php

namespace App\Filament\Resources\TicketAttachments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TicketAttachmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('ticket_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('path')
                    ->required(),
                TextInput::make('original_name')
                    ->required(),
                TextInput::make('mime'),
                TextInput::make('size')
                    ->numeric(),
            ]);
    }
}
