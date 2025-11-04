<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Governance';

    protected static ?string $navigationLabel = 'Audit Logs';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('Occurred')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_type')
                    ->label('Event')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('actor.name')
                    ->label('Actor')
                    ->placeholder('System')
                    ->searchable(),
                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('Target Type')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payload')
                    ->label('Details')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state) : '—')
                    ->limit(60),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->options(fn () => AuditLog::query()
                        ->select('event_type')
                        ->distinct()
                        ->orderBy('event_type')
                        ->pluck('event_type', 'event_type')),
                Tables\Filters\Filter::make('occurred_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('occurred_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('occurred_at', '<=', $date));
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('manager', 'ops_manager', 'hr', 'admin') ?? false;
    }
}
