<?php

namespace App\Filament\Resources\PerformanceSnapshots;

use App\Filament\Resources\PerformanceSnapshots\Pages\CreatePerformanceSnapshot;
use App\Filament\Resources\PerformanceSnapshots\Pages\EditPerformanceSnapshot;
use App\Filament\Resources\PerformanceSnapshots\Pages\ListPerformanceSnapshots;
use App\Filament\Resources\PerformanceSnapshots\Schemas\PerformanceSnapshotForm;
use App\Filament\Resources\PerformanceSnapshots\Tables\PerformanceSnapshotsTable;
use App\Models\PerformanceSnapshot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PerformanceSnapshotResource extends Resource
{
    protected static ?string $model = PerformanceSnapshot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|\UnitEnum|null $navigationGroup = 'Analytics';

    protected static ?string $navigationLabel = 'Performance Snapshots';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return PerformanceSnapshotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerformanceSnapshotsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPerformanceSnapshots::route('/'),
            'create' => CreatePerformanceSnapshot::route('/create'),
            'edit' => EditPerformanceSnapshot::route('/{record}/edit'),
        ];
    }
}
