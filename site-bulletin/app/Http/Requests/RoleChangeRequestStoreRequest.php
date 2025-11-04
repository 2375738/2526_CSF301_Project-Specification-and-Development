<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleChangeRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('employee', 'manager', 'ops_manager', 'hr', 'admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_user_id' => ['required', 'exists:users,id'],
            'requested_role' => [
                'required',
                Rule::in(array_map(fn (UserRole $role) => $role->value, UserRole::cases())),
            ],
            'department_id' => ['nullable', 'exists:departments,id'],
            'justification' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
