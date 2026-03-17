<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $body = trim((string) $this->input('body', ''));
            $hasAttachments = $this->hasFile('attachments');

            if ($body === '' && ! $hasAttachments) {
                $validator->errors()->add('body', 'Enter a message or attach at least one file.');
            }
        });
    }
}
