<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketAttachmentRequest extends FormRequest
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
            'attachment' => [
                'required',
                'file',
                'max:10240',
                'mimetypes:image/jpeg,image/png,application/pdf',
            ],
        ];
    }
}
