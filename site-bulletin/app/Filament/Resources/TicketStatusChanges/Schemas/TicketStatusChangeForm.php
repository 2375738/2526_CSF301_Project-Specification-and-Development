<?php

namespace App\Filament\Resources\TicketStatusChanges\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TicketStatusChangeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('ticket_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->numeric(),
                TextInput::make('from_status'),
                TextInput::make('to_status')
                    ->required(),
                TextInput::make('reason'),
            ]);
    }
}
