<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(array_map(fn (TicketStatus $status) => $status->value, TicketStatus::cases())),
            ],
            'priority' => [
                'nullable',
                Rule::in(array_map(fn (TicketPriority $priority) => $priority->value, TicketPriority::cases())),
            ],
            'assignee_id' => [
                'nullable',
                'exists:users,id',
            ],
            'duplicate_of_id' => [
                'nullable',
                'integer',
                'exists:tickets,id',
            ],
            'comment' => ['nullable', 'string'],
            'is_private' => ['sometimes', 'boolean'],
        ];
    }
}
