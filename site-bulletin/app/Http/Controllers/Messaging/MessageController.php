<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageStoreRequest;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function store(MessageStoreRequest $request, Conversation $conversation): RedirectResponse
    {
        $conversation->loadMissing('participants');

        $this->authorize('message', $conversation);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $this->normaliseBody($request->validated('body')),
            'is_system' => false,
        ]);

        $this->persistAttachments($request, $message);

        $conversation->touch();
        $conversation->markReadFor($request->user());

        return Redirect::route('messages.show', $conversation)
            ->with('status', 'Message sent.');
    }

    protected function persistAttachments(MessageStoreRequest $request, Message $message): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $message->attachments()->create([
                'user_id' => $request->user()->id,
                'disk' => 'attachments',
                'path' => $file->storeAs(
                    'messages/' . $message->conversation_id . '/' . $message->id,
                    Str::uuid() . '.' . $file->getClientOriginalExtension(),
                    'attachments'
                ),
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    protected function normaliseBody(?string $body): ?string
    {
        $body = trim((string) $body);

        return $body === '' ? null : $body;
    }
}
