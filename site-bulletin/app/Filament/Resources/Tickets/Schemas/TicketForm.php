<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('requester_id')
                    ->relationship('requester', 'name')
                    ->required()
                    ->searchable(),
                Select::make('assignee_id')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->placeholder('Unassigned'),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->required(),
                Select::make('priority')
                    ->options(collect(TicketPriority::cases())->mapWithKeys(fn ($priority) => [$priority->value => ucfirst($priority->value)])->all())
                    ->required(),
                Select::make('status')
                    ->options(collect(TicketStatus::cases())->mapWithKeys(fn ($status) => [$status->value => ucfirst(str_replace('_', ' ', $status->value))])->all())
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->rows(5)
                    ->columnSpanFull()
                    ->required(),
                TextInput::make('location')
                    ->maxLength(255)
                    ->columnSpanFull(),
                DateTimePicker::make('closed_at')
                    ->seconds(false)
                    ->label('Closed at')
                    ->columnSpanFull(),
            ]);
    }
}
