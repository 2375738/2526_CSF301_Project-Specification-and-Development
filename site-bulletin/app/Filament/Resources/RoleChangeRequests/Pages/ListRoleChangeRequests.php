<?php

namespace App\Filament\Resources\RoleChangeRequests\Pages;

use App\Filament\Resources\RoleChangeRequests\RoleChangeRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListRoleChangeRequests extends ListRecords
{
    protected static string $resource = RoleChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
