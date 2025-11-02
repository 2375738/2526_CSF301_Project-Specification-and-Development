<?php

namespace App\Filament\Resources\PerformanceSnapshots\Pages;

use App\Filament\Resources\PerformanceSnapshots\PerformanceSnapshotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPerformanceSnapshots extends ListRecords
{
    protected static string $resource = PerformanceSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
