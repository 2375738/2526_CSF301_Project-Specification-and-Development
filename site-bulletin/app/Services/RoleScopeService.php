<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RoleScopeService
{
    public function canViewAllDepartments(User $user): bool
    {
        return $user->hasRole('ops_manager', 'hr', 'admin');
    }

    public function canManageAllDepartments(User $user): bool
    {
        return $user->hasRole('ops_manager', 'hr', 'admin');
    }

    public function viewableDepartmentIds(User $user): ?Collection
    {
        if ($this->canViewAllDepartments($user)) {
            return null;
        }

        if ($user->hasRole('manager')) {
            return $this->managedDepartmentIds($user);
        }

        return $user->departmentIds();
    }

    public function manageableDepartmentIds(User $user): ?Collection
    {
        if ($this->canManageAllDepartments($user)) {
            return null;
        }

        if ($user->hasRole('manager')) {
            return $this->managedDepartmentIds($user);
        }

        return collect();
    }

    public function managedDepartmentIds(User $user): Collection
    {
        return $user->managedDepartments()
            ->pluck('departments.id')
            ->unique()
            ->values();
    }

    public function canViewDepartment(User $user, ?int $departmentId): bool
    {
        if ($departmentId === null) {
            return $this->canViewAllDepartments($user);
        }

        $departmentIds = $this->viewableDepartmentIds($user);

        return $departmentIds === null || $departmentIds->contains($departmentId);
    }

    public function canManageDepartment(User $user, ?int $departmentId): bool
    {
        if ($departmentId === null) {
            return $this->canManageAllDepartments($user);
        }

        $departmentIds = $this->manageableDepartmentIds($user);

        return $departmentIds === null || $departmentIds->contains($departmentId);
    }

    public function applyViewableDepartmentScope(Builder $query, User $user, string $column = 'department_id'): Builder
    {
        return $this->applyDepartmentScope($query, $this->viewableDepartmentIds($user), $column);
    }

    public function applyManageableDepartmentScope(Builder $query, User $user, string $column = 'department_id'): Builder
    {
        return $this->applyDepartmentScope($query, $this->manageableDepartmentIds($user), $column);
    }

    public function departmentOptionsForManagement(User $user): Collection
    {
        $departmentIds = $this->manageableDepartmentIds($user);

        return Department::query()
            ->when($departmentIds !== null, fn ($query) => $query->whereIn('id', $departmentIds))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function applyDepartmentScope(Builder $query, ?Collection $departmentIds, string $column): Builder
    {
        if ($departmentIds === null) {
            return $query;
        }

        if ($departmentIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $departmentIds);
    }
}
