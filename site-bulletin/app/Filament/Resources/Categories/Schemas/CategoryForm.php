<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Department;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();

        $audienceOptions = [
            'all' => 'All employees',
            'department' => 'Specific department',
            'managers' => 'Managers only',
        ];

        if ($user && ! ($user->isHr() || $user->isAdmin())) {
            $audienceOptions = ['department' => 'Department members'];
        }

        $departmentOptions = Department::query()
            ->when(
                $user && ! ($user->isHr() || $user->isAdmin()),
                fn ($query) => $query->whereIn('id', $user->departmentIds())
            )
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(120),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_sensitive')
                    ->label('Sensitive (HR)')
                    ->helperText('Hide content from employees without manager/access rights.')
                    ->columnSpanFull(),
                Select::make('audience')
                    ->options($audienceOptions)
                    ->default(array_key_first($audienceOptions))
                    ->required()
                    ->reactive()
                    ->columnSpanFull(),
                Select::make('department_id')
                    ->label('Department')
                    ->options($departmentOptions)
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get) => $get('audience') === 'department')
                    ->disabled(fn () => empty($departmentOptions))
                    ->columnSpanFull(),
            ]);
    }
}
