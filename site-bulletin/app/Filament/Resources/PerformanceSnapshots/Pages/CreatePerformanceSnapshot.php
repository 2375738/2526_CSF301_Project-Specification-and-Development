<?php

namespace App\Filament\Resources\PerformanceSnapshots\Pages;

use App\Filament\Resources\PerformanceSnapshots\PerformanceSnapshotResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePerformanceSnapshot extends CreateRecord
{
    protected static string $resource = PerformanceSnapshotResource::class;
}
