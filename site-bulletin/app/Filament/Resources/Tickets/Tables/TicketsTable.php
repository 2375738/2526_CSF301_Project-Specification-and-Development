<?php

namespace App\Filament\Resources\Tickets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['requester', 'assignee', 'category'])
                ->leftJoin('s_l_a_settings', 's_l_a_settings.priority', '=', 'tickets.priority')
                ->select('tickets.*')
                ->selectRaw('DATE_ADD(tickets.created_at, INTERVAL COALESCE(s_l_a_settings.resolution_minutes, 0) MINUTE) as resolution_due_at')
            )
            ->columns([
                TextColumn::make('title')
                    ->limit(40)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('requester.name')
                    ->label('Requester')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),
                TextColumn::make('resolution_due_at')
                    ->label('Resolution Due')
                    ->dateTime('M j, H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->since()
                    ->label('Opened')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('priority')
                    ->options([
                        'critical' => 'Critical',
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'triaged' => 'Triaged',
                        'in_progress' => 'In Progress',
                        'waiting_employee' => 'Waiting Employee',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                        'reopened' => 'Reopened',
                        'cancelled' => 'Cancelled',
                    ]),
                TernaryFilter::make('duplicate_of_id')
                    ->label('Marked as duplicate'),
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
