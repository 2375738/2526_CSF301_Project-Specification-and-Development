<?php

namespace App\Filament\Resources\PerformanceSnapshots\Pages;

use App\Filament\Resources\PerformanceSnapshots\PerformanceSnapshotResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPerformanceSnapshot extends EditRecord
{
    protected static string $resource = PerformanceSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
