<?php

namespace App\Filament\Resources\Departments\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected function memberFields(): array
    {
        return [
            Select::make('user_id')
                ->relationship('members', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->label('User'),
            Select::make('role')
                ->label('Department Role')
                ->options([
                    'member' => 'Member',
                    'manager' => 'Manager',
                    'hr_manager' => 'HR Manager',
                    'observer' => 'Observer',
                ])
                ->default('member')
                ->required(),
            Toggle::make('is_primary')
                ->label('Primary Department')
                ->helperText('Marks this department as the user’s primary assignment.'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->memberFields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Member')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->copyable(),
                TextColumn::make('pivot.role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                IconColumn::make('pivot.is_primary')
                    ->boolean()
                    ->label('Primary'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->label('Add member')
                    ->form($this->memberFields()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form($this->memberFields()),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}
