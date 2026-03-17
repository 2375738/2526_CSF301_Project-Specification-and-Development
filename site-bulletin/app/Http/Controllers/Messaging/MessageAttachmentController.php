<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Models\MessageAttachment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentController extends Controller
{
    public function download(MessageAttachment $attachment): StreamedResponse
    {
        $attachment->loadMissing('message.conversation.participants');

        $conversation = $attachment->message->conversation;

        $this->authorize('view', $conversation);

        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404, 'Attachment file not found');
        }

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name
        );
    }

    public function preview(MessageAttachment $attachment): Response|StreamedResponse
    {
        $attachment->loadMissing('message.conversation.participants');

        $conversation = $attachment->message->conversation;

        $this->authorize('view', $conversation);

        abort_unless($attachment->isImage(), 404);

        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404, 'Attachment file not found');
        }

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"',
            ]
        );
    }
}
