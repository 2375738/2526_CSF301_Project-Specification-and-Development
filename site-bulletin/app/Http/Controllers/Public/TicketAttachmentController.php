<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketAttachmentRequest;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function store(
        TicketAttachmentRequest $request,
        Ticket $ticket,
        NotificationService $notifier
    ): RedirectResponse {
        $this->authorize('upload', $ticket);

        $file = $request->file('attachment');
        $user = $request->user();

        $attachment = $ticket->attachments()->create([
            'user_id' => $user->id,
            'disk' => 'attachments',
            'path' => $file->storeAs(
                'tickets/' . $ticket->id,
                Str::uuid() . '.' . $file->getClientOriginalExtension(),
                'attachments'
            ),
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        $notifier->commentAdded($ticket, $user, false);

        return back()->with('status', "Attachment {$attachment->original_name} uploaded.");
    }

    public function download(TicketAttachment $attachment): StreamedResponse
    {
        $ticket = $attachment->ticket;

        $this->authorize('view', $ticket);

        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404, 'Attachment file not found');
        }

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name
        );
    }
}
