<?php

namespace App\Filament\Resources\SLASettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SLASettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                TextColumn::make('first_response_minutes')
                    ->label('First Response')
                    ->suffix(' mins')
                    ->sortable(),
                TextColumn::make('resolution_minutes')
                    ->label('Resolution')
                    ->suffix(' mins')
                    ->sortable(),
                TextColumn::make('pause_statuses')
                    ->badge()
                    ->separator(', ')
                    ->formatStateUsing(fn ($state) => collect($state)
                        ->map(fn ($value) => ucfirst(str_replace('_', ' ', $value)))
                        ->implode(', '))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
