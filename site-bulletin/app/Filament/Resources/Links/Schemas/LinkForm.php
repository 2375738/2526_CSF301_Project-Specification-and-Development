<?php

namespace App\Filament\Resources\Links\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable(),
                TextInput::make('label')
                    ->required()
                    ->maxLength(120),
                TextInput::make('url')
                    ->required()
                    ->url()
                    ->columnSpanFull(),
                Toggle::make('is_hot')
                    ->label('Hot topic')
                    ->helperText('Showcase this link as a hot topic on the dashboard.'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('order')
                    ->numeric()
                    ->default(0)
                    ->columnSpanFull(),
            ]);
    }
}
