<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('duplicate_of_id')) {
                return;
            }

            $ticket = $this->route('ticket');
            $primary = Ticket::query()->find($this->integer('duplicate_of_id'));
            $user = $this->user();

            if (! $ticket instanceof Ticket || ! $primary || ! $user) {
                return;
            }

            if ($ticket->is($primary)) {
                $validator->errors()->add('duplicate_of_id', 'A ticket cannot be marked as a duplicate of itself.');
            }

            if ($primary->duplicate_of_id !== null) {
                $validator->errors()->add('duplicate_of_id', 'Select the primary ticket instead of another duplicate.');
            }

            if (! $user->can('view', $primary)) {
                $validator->errors()->add('duplicate_of_id', 'The selected primary ticket is not available.');
            }

            if ($ticket->department_id && $primary->department_id && $ticket->department_id !== $primary->department_id) {
                $validator->errors()->add('duplicate_of_id', 'Duplicate tickets must belong to the same department.');
            }
        });
    }
}
