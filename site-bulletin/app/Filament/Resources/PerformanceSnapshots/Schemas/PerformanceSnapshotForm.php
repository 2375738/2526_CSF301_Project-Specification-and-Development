<?php

namespace App\Filament\Resources\PerformanceSnapshots\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PerformanceSnapshotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                DatePicker::make('week_start')
                    ->required()
                    ->native(false),
                TextInput::make('units_per_hour')
                    ->nullable()
                    ->numeric()
                    ->suffix('uph'),
                TextInput::make('rank_percentile')
                    ->nullable()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
            ]);
    }
}
