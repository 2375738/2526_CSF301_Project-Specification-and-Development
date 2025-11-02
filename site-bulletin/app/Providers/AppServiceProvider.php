<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
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

        Gate::policy(Ticket::class, TicketPolicy::class);

        Gate::define('manage-public-content', fn (User $user) => $user->hasRole('manager', 'admin'));
        Gate::define('manage-sla', fn (User $user) => $user->isAdmin());
        Gate::define('view-analytics', fn (User $user) => $user->hasRole('manager', 'admin'));
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());
    }
}
