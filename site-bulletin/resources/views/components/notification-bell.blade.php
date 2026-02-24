<div x-data="{ open: false }" class="relative">
    <!-- Bell Icon -->
    <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none">
        <span class="sr-only">Notifications</span>
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        
        <!-- Unread Badge -->
        @if(auth()->user()->unreadNotifications->count() > 0)
            <span class="absolute top-1 right-1 block h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white"></span>
        @endif
    </button>

    <!-- Dropdown Panel -->
    <div x-show="open" 
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
         style="display: none;">
        
        <div class="py-1">
            <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                @if(auth()->user()->unreadNotifications->count() > 0)
                    <form action="{{ route('notifications.read-all') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Mark all read
                        </button>
                    </form>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto">
                @forelse(auth()->user()->unreadNotifications as $notification)
                    <div class="relative group">
                        <form action="{{ route('notifications.read', $notification->id) }}" method="POST" id="mark-read-{{ $notification->id }}">
                            @csrf
                            @method('PATCH')
                        </form>
                        
                        <a href="#" onclick="event.preventDefault(); document.getElementById('mark-read-{{ $notification->id }}').submit();" class="block px-4 py-3 hover:bg-gray-50 transition duration-150 ease-in-out">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 text-blue-600">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="ml-3 w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $notification->data['title'] ?? 'Notification' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $notification->data['message'] ?? '' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-gray-500">
                        No new notifications.
                    </div>
                @endforelse
            </div>
            
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-2 text-center">
                <a href="#" class="text-xs font-medium text-gray-600 hover:text-gray-900">View all notifications</a>
            </div>
        </div>
    </div>
</div>
