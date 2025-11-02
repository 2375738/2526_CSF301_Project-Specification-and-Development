<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Category;
use App\Models\Ticket;
use App\Services\NotificationService;
use App\Services\SLAService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportTicketController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', Ticket::class);

        $categories = Category::orderBy('order')->get();
        $title = old('title', $request->query('title'));
        $categoryId = (int) old('category_id', $request->query('category_id'));

        $similarTickets = $this->similarTickets(
            $request->user(),
            $title,
            $categoryId ?: null
        );

        return view('tickets.report', [
            'categories' => $categories,
            'similarTickets' => $similarTickets,
        ]);
    }

    public function store(
        StoreTicketRequest $request,
        SLAService $slaService,
        NotificationService $notifier
    ): RedirectResponse {
        $user = $request->user();

        $ticket = Ticket::create([
            'requester_id' => $user->id,
            'category_id' => $request->integer('category_id'),
            'priority' => TicketPriority::Medium->value,
            'status' => TicketStatus::New->value,
            'title' => trim((string) $request->input('title')),
            'description' => trim((string) $request->input('description')),
            'location' => $request->filled('location') ? trim((string) $request->input('location')) : null,
        ]);

        $ticket->statusChanges()->create([
            'user_id' => $user->id,
            'from_status' => null,
            'to_status' => TicketStatus::New->value,
            'reason' => 'Ticket created by requester',
        ]);

        if ($file = $request->file('attachment')) {
            $ticket->attachments()->create([
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
        }

        $notifier->ticketCreated($ticket);

        $targets = $slaService->targets($ticket->priority);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with(
                'status',
                "Ticket #{$ticket->id} created. Target first response: {$targets['first_response_minutes']} minutes."
            );
    }

    public function link(Request $request, NotificationService $notifier): RedirectResponse
    {
        $data = $request->validate([
            'ticket_id' => ['required', 'exists:tickets,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $ticket = Ticket::findOrFail($data['ticket_id']);

        $this->authorize('comment', $ticket);

        $commentBody = trim($data['message'] ?? '') ?: 'Marked as related issue by requester.';

        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $commentBody,
            'is_private' => false,
        ]);

        $notifier->commentAdded($ticket, $comment->author, false);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Linked to ticket #{$ticket->id}. We'll keep you posted there.");
    }

    protected function similarTickets(?\App\Models\User $user, ?string $title, ?int $categoryId): Collection
    {
        if (! $title || mb_strlen($title) < 3) {
            return collect();
        }

        $keywords = collect(preg_split('/\s+/', Str::lower($title)))
            ->filter(fn ($word) => mb_strlen($word) >= 3)
            ->take(3);

        if ($keywords->isEmpty()) {
            return collect();
        }

        $query = Ticket::query()
            ->open()
            ->whereNull('duplicate_of_id')
            ->whereHas('category', fn ($q) => $q->where('is_sensitive', false))
            ->with(['requester'])
            ->orderByDesc('created_at')
            ->limit(5);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $query->where(function ($q) use ($keywords, $user) {
            foreach ($keywords as $word) {
                $q->orWhere('title', 'like', '%' . $word . '%');
            }

            if ($user && ! $user->hasRole('manager', 'admin')) {
                $q->orWhere('requester_id', $user->id);
            }
        });

        return $query->get();
    }
}
