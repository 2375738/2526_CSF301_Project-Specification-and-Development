<?php

namespace App\Filament\Resources\RoleChangeRequests;

use App\Enums\UserRole;
use App\Filament\Resources\RoleChangeRequests\Pages\ListRoleChangeRequests;
use App\Models\RoleChangeRequest;
use App\Services\AuditLogger;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class RoleChangeRequestResource extends Resource
{
    protected static ?string $model = RoleChangeRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Governance';

    protected static ?string $navigationLabel = 'Role Requests';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requester')
                    ->searchable(),
                Tables\Columns\TextColumn::make('target.name')
                    ->label('Target')
                    ->searchable(),
                Tables\Columns\TextColumn::make('requested_role')
                    ->label('Requested Role')
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->placeholder('—'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => RoleChangeRequest::STATUS_PENDING,
                        'success' => RoleChangeRequest::STATUS_APPROVED,
                        'danger' => RoleChangeRequest::STATUS_REJECTED,
                    ])
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        RoleChangeRequest::STATUS_PENDING => 'Pending',
                        RoleChangeRequest::STATUS_APPROVED => 'Approved',
                        RoleChangeRequest::STATUS_REJECTED => 'Rejected',
                    ])
                    ->default(RoleChangeRequest::STATUS_PENDING),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon(Heroicon::MiniCheckCircle)
                    ->requiresConfirmation()
                    ->visible(fn (RoleChangeRequest $record) => $record->status === RoleChangeRequest::STATUS_PENDING)
                    ->form([
                        Forms\Components\Textarea::make('decision_notes')
                            ->label('Notes')
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->action(function (RoleChangeRequest $record, array $data) {
                        $user = auth()->user();
                        abort_unless($user?->hasRole('hr', 'admin'), 403);

                        $record->markApproved($user, $data['decision_notes'] ?? null);

                        $target = $record->target;

                        if ($target) {
                            $newRole = UserRole::tryFrom($record->requested_role);

                            if ($newRole) {
                                $target->role = $newRole;
                            }

                            if ($record->department_id) {
                                $target->primary_department_id = $record->department_id;
                            }

                            $target->save();
                        }

                        app(AuditLogger::class)->log('role.request.approved', $record, [
                            'target_user_id' => $record->target_user_id,
                            'requested_role' => $record->requested_role,
                            'approver_id' => $user?->id,
                        ], $user);
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon(Heroicon::MiniXCircle)
                    ->visible(fn (RoleChangeRequest $record) => $record->status === RoleChangeRequest::STATUS_PENDING)
                    ->form([
                        Forms\Components\Textarea::make('decision_notes')
                            ->label('Reason')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->action(function (RoleChangeRequest $record, array $data) {
                        $user = auth()->user();
                        abort_unless($user?->hasRole('hr', 'admin'), 403);

                        $record->markRejected($user, $data['decision_notes']);

                        app(AuditLogger::class)->log('role.request.rejected', $record, [
                            'target_user_id' => $record->target_user_id,
                            'requested_role' => $record->requested_role,
                            'approver_id' => $user?->id,
                        ], $user);
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoleChangeRequests::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('hr', 'admin') ?? false;
    }
}
