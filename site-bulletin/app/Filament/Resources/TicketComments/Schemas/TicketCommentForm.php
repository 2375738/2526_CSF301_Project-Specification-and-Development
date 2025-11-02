<?php

namespace App\Filament\Resources\TicketComments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TicketCommentForm
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
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_private')
                    ->required(),
            ]);
    }
}
