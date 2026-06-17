<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;

class NavigationViewData
{
    public function forRequest(Request $request): array
    {
        $user = $request->user();
        $showGovernance = $user?->hasRole('manager', 'ops_manager', 'hr', 'admin') ?? false;
        $unreadConversationCount = $user ? $this->unreadConversationCount($user) : 0;

        $desktopNavItems = $user
            ? $this->desktopNavItems($user, $showGovernance, $unreadConversationCount)
            : [];

        return [
            'showGovernance' => $showGovernance,
            'user' => $user,
            'unreadAnnouncementCount' => $user ? $this->unreadAnnouncementCount($user) : 0,
            'unreadConversationCount' => $unreadConversationCount,
            'desktopNavItems' => $desktopNavItems,
            'mobileNavItems' => $user ? $this->mobileNavItems($user, $desktopNavItems, $showGovernance) : [],
            'userInitials' => $this->userInitials($user),
            'userRoleLabel' => $this->userRoleLabel($user),
            'showPrototypeFooter' => (bool) config('site_bulletin.prototype_footer_enabled'),
        ];
    }

    protected function desktopNavItems(User $user, bool $showGovernance, int $unreadConversationCount): array
    {
        $items = [
            'dashboard' => $this->navItem('Dashboard', route('home'), 'dashboard', request()->routeIs('home', 'dashboard')),
        ];

        if ($user->isEmployee()) {
            $items['my-work'] = $this->navItem('My Work', route('my-work.index'), 'my-work', request()->routeIs('my-work.*'));
        }

        $items['messages'] = $this->navItem('Messages', route('messages.index'), 'messages', request()->routeIs('messages.*'), $unreadConversationCount);
        $items['knowledge'] = $this->navItem('Knowledge', route('knowledge.index'), 'knowledge', request()->routeIs('knowledge.*'));
        $items['tickets'] = $this->navItem('Tickets', route('tickets.index'), 'tasks', request()->routeIs('tickets.*'));
        $items['profile'] = $this->navItem('Profile', route('profile.edit'), 'profile', request()->routeIs('profile.*'));

        if ($showGovernance) {
            $items['analytics'] = $this->navItem('Analytics', route('analytics.index'), 'analytics', request()->routeIs('analytics.*'));
            $items['governance'] = $this->navItem('Governance', route('governance.index'), 'governance', request()->routeIs('governance.*'));
        }

        return $items;
    }

    protected function mobileNavItems(User $user, array $desktopNavItems, bool $showGovernance): array
    {
        $keys = $user->isEmployee()
            ? ['dashboard', 'my-work', 'messages', 'tickets', 'profile']
            : ['dashboard', 'messages', 'tickets', 'profile'];

        $items = collect($keys)
            ->map(fn (string $key) => $desktopNavItems[$key] ?? null)
            ->filter()
            ->values()
            ->all();

        if (! $user->isEmployee()) {
            $items[] = $showGovernance
                ? $this->navItem('More', route('governance.index'), 'governance', request()->routeIs('governance.*', 'analytics.*'))
                : $desktopNavItems['knowledge'];
        }

        return array_slice($items, 0, 5);
    }

    protected function navItem(string $label, string $route, string $icon, bool $active, int $badge = 0): array
    {
        return compact('label', 'route', 'active', 'icon', 'badge');
    }

    protected function unreadAnnouncementCount(User $user): int
    {
        return Announcement::query()
            ->active()
            ->visibleTo($user)
            ->whereDoesntHave('readers', fn ($query) => $query->where('users.id', $user->id))
            ->count();
    }

    protected function unreadConversationCount(User $user): int
    {
        return Conversation::query()
            ->forUser($user)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('users.id', $user->id)
                    ->where(function ($sub) {
                        $sub->whereNull('conversation_participants.last_read_at')
                            ->orWhereColumn('conversation_participants.last_read_at', '<', 'conversations.updated_at');
                    });
            })
            ->count();
    }

    protected function userInitials(?User $user): string
    {
        if (! $user) {
            return '';
        }

        return collect(explode(' ', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    }

    protected function userRoleLabel(?User $user): ?string
    {
        return $user?->role?->label()
            ?? ($user?->role ? str($user->role)->replace('_', ' ')->title()->toString() : null);
    }
}
