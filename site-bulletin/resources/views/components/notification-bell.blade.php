@php
    $notifications = auth()->user()->unreadNotifications()->latest()->take(8)->get();
    $unreadCount = auth()->user()->unreadNotifications()->count();
@endphp

<div x-data="{ open: false }" class="relative">
    <button
        type="button"
        @click="open = !open"
        class="relative inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
        :aria-expanded="open.toString()"
        aria-haspopup="true"
    >
        <span>Alerts</span>
        @if($unreadCount > 0)
            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-blue-600 px-1.5 text-[11px] font-bold text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute right-0 z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] origin-top-right overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-slate-200"
        style="display: none;"
    >
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Action needed</h3>
                <p class="text-xs text-slate-500">{{ $unreadCount }} unread {{ \Illuminate\Support\Str::plural('alert', $unreadCount) }}</p>
            </div>
            @if($unreadCount > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                        Clear all
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                @php
                    $type = $notification->data['type'] ?? 'notice';
                    $actionLabel = $notification->data['action_label'] ?? match ($type) {
                        'ticket' => 'Open ticket',
                        'message' => 'Reply',
                        'announcement' => 'Read update',
                        default => 'Open',
                    };
                    $typeLabel = match ($type) {
                        'ticket' => 'Ticket',
                        'message' => 'Message',
                        'announcement' => 'Update',
                        default => 'Alert',
                    };
                @endphp
                <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="block w-full px-4 py-3 text-left transition hover:bg-slate-50 focus:bg-blue-50 focus:outline-none">
                        <span class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex min-w-14 justify-center rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-bold uppercase text-slate-600">
                                {{ $typeLabel }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Notification' }}</span>
                                <span class="mt-1 block text-xs leading-5 text-slate-600">{{ $notification->data['message'] ?? '' }}</span>
                                <span class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ $actionLabel }}</span>
                                </span>
                            </span>
                        </span>
                    </button>
                </form>
            @empty
                <div class="px-4 py-8 text-center">
                    <p class="text-sm font-semibold text-slate-800">No actions waiting</p>
                    <p class="mt-1 text-xs text-slate-500">New ticket updates, messages, and announcements will appear here.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
