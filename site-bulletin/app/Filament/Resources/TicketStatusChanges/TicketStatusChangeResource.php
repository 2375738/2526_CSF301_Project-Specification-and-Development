<?php

namespace App\Filament\Resources\TicketStatusChanges;

use App\Filament\Resources\TicketStatusChanges\Pages\CreateTicketStatusChange;
use App\Filament\Resources\TicketStatusChanges\Pages\EditTicketStatusChange;
use App\Filament\Resources\TicketStatusChanges\Pages\ListTicketStatusChanges;
use App\Filament\Resources\TicketStatusChanges\Schemas\TicketStatusChangeForm;
use App\Filament\Resources\TicketStatusChanges\Tables\TicketStatusChangesTable;
use App\Models\TicketStatusChange;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TicketStatusChangeResource extends Resource
{
    protected static ?string $model = TicketStatusChange::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return TicketStatusChangeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketStatusChangesTable::configure($table);
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
            'index' => ListTicketStatusChanges::route('/'),
            'create' => CreateTicketStatusChange::route('/create'),
            'edit' => EditTicketStatusChange::route('/{record}/edit'),
        ];
    }
}
