<?php

namespace App\Filament\Resources\Announcements\Schemas;

use App\Models\Department;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();

        $audienceOptions = [
            'all' => 'Entire site',
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
            ->components([
                Hidden::make('author_id')
                    ->default(fn () => auth()->id()),
                TextInput::make('title')
                    ->required(),
                Textarea::make('body')
                    ->columnSpanFull()
                    ->rows(5)
                    ->required(),
                Select::make('audience')
                    ->options($audienceOptions)
                    ->default(array_key_first($audienceOptions))
                    ->required()
                    ->reactive(),
                Select::make('department_id')
                    ->label('Department')
                    ->options($departmentOptions)
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get) => $get('audience') === 'department')
                    ->disabled(fn () => empty($departmentOptions))
                    ->columnSpanFull(),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                Toggle::make('is_pinned')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
