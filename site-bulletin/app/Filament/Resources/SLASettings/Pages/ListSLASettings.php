<?php

namespace App\Filament\Resources\SLASettings\Pages;

use App\Filament\Resources\SLASettings\SLASettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSLASettings extends ListRecords
{
    protected static string $resource = SLASettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
