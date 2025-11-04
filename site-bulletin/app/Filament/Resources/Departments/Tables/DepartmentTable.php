<?php

namespace App\Filament\Resources\Departments\Tables;

use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepartmentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                BadgeColumn::make('members_count')
                    ->counts('members')
                    ->label('Members')
                    ->color('primary'),
                TextColumn::make('color')
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? ''))
                    ->badge()
                    ->color(fn ($state) => $state),
            ])
            ->defaultSort('name');
    }
}
