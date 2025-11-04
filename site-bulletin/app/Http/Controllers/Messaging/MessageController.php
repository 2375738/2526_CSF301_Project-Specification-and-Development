<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageStoreRequest;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class MessageController extends Controller
{
    public function store(MessageStoreRequest $request, Conversation $conversation): RedirectResponse
    {
        $conversation->loadMissing('participants');

        $this->authorize('message', $conversation);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
            'is_system' => false,
        ]);

        $conversation->touch();
        $conversation->markReadFor($request->user());

        return Redirect::route('messages.show', $conversation)
            ->with('status', 'Message sent.');
    }
}
