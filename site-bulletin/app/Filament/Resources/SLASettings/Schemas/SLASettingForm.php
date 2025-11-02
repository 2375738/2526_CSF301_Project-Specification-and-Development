<?php

namespace App\Filament\Resources\SLASettings\Schemas;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SLASettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('priority')
                    ->options(collect(TicketPriority::cases())->mapWithKeys(fn ($priority) => [$priority->value => ucfirst($priority->value)])->all())
                    ->required()
                    ->disabledOn('edit'),
                TextInput::make('first_response_minutes')
                    ->numeric()
                    ->required()
                    ->suffix('mins'),
                TextInput::make('resolution_minutes')
                    ->numeric()
                    ->required()
                    ->suffix('mins'),
                Select::make('pause_statuses')
                    ->multiple()
                    ->options(collect(TicketStatus::cases())->mapWithKeys(fn ($status) => [$status->value => ucfirst(str_replace('_', ' ', $status->value))])->all())
                    ->columnSpanFull()
                    ->helperText('Statuses that pause SLA timers (e.g. waiting for employee response).'),
            ]);
    }
}
