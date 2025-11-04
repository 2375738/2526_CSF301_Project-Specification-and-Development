<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'subject' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:direct,department'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'recipients' => ['nullable', 'array'],
            'recipients.*' => ['integer', 'exists:users,id'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
