<?php

namespace App\Filament\Resources\Announcements\Tables;

use App\Services\RoleScopeService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $user = auth()->user();

                if (! $user) {
                    return $query->whereRaw('1 = 0');
                }

                if (app(RoleScopeService::class)->canViewAllDepartments($user)) {
                    return $query;
                }

                $departmentIds = app(RoleScopeService::class)->viewableDepartmentIds($user) ?? collect();

                return $query->where(function ($inner) use ($departmentIds) {
                    $inner->where('author_id', auth()->id());

                    if ($departmentIds->isNotEmpty()) {
                        $inner->orWhere(function ($departmentQuery) use ($departmentIds) {
                            $departmentQuery
                                ->where('audience', 'department')
                                ->whereIn('department_id', $departmentIds);
                        });
                    }
                });
            })
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('audience')
                    ->badge()
                    ->label('Audience')
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->colors([
                        'gray' => 'low',
                        'info' => 'medium',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ]),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_pinned')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
