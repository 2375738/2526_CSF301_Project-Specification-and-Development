<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\RoleChangeRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\RoleChangeRequestPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->isAdmin() ? true : null;
        });

        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(RoleChangeRequest::class, RoleChangeRequestPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);

        Gate::define('manage-public-content', fn (User $user) => $user->hasRole('manager', 'ops_manager', 'hr', 'admin'));
        Gate::define('manage-sla', fn (User $user) => $user->isAdmin());
        Gate::define('view-analytics', fn (User $user) => $user->hasRole('manager', 'ops_manager', 'hr', 'admin'));
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());
    }
}
