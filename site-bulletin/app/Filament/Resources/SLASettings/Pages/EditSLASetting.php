<?php

namespace App\Filament\Resources\SLASettings\Pages;

use App\Filament\Resources\SLASettings\SLASettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSLASetting extends EditRecord
{
    protected static string $resource = SLASettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
