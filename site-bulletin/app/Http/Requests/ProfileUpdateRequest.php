<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        $this->merge([
            'email_notifications_enabled' => $this->has('email_notifications_enabled')
                ? $this->input('email_notifications_enabled')
                : (int) ($user->email_notifications_enabled ?? true),
            'slack_notifications_enabled' => $this->has('slack_notifications_enabled')
                ? $this->input('slack_notifications_enabled')
                : (int) ($user->slack_notifications_enabled ?? false),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'job_title' => ['nullable', 'string', 'max:255'],
            'employee_id' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:255'],
            'pronouns' => ['nullable', 'string', 'max:50'],
            'emergency_contact' => ['nullable', 'string', 'max:500'],
            'email_notifications_enabled' => ['required', 'boolean'],
            'slack_notifications_enabled' => ['required', 'boolean'],
        ];
    }
}
