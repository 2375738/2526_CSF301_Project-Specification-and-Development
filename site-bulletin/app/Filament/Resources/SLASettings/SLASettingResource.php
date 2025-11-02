<?php

namespace App\Filament\Resources\SLASettings;

use App\Filament\Resources\SLASettings\Pages\CreateSLASetting;
use App\Filament\Resources\SLASettings\Pages\EditSLASetting;
use App\Filament\Resources\SLASettings\Pages\ListSLASettings;
use App\Filament\Resources\SLASettings\Schemas\SLASettingForm;
use App\Filament\Resources\SLASettings\Tables\SLASettingsTable;
use App\Models\SLASetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SLASettingResource extends Resource
{
    protected static ?string $model = SLASetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'SLA Policies';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return SLASettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SLASettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSLASettings::route('/'),
            'create' => CreateSLASetting::route('/create'),
            'edit' => EditSLASetting::route('/{record}/edit'),
        ];
    }
}
