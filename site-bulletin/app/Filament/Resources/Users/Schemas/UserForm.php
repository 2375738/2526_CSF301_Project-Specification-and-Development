<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(table: 'users', column: 'email', ignoreRecord: true),
                Select::make('role')
                    ->options(UserRole::options())
                    ->required()
                    ->native(false),
                Select::make('primary_department_id')
                    ->relationship('primaryDepartment', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Primary Department')
                    ->placeholder('Assign department')
                    ->columnSpanFull(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->confirmed()
                    ->columnSpanFull(),
                TextInput::make('password_confirmation')
                    ->password()
                    ->revealable()
                    ->dehydrated(false)
                    ->required(fn (string $operation, $get) => $operation === 'create' || filled($get('password')))
                    ->label('Confirm Password')
                    ->columnSpanFull(),
            ]);
    }
}

