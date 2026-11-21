<?php

namespace App\Providers;

use App\Models\InventoryTransaction;
use App\Models\User;
use App\Observers\InventoryTransactionObserver;
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
        InventoryTransaction::observe(InventoryTransactionObserver::class);

        Gate::define('manage-procurement', fn (User $user): bool => (bool) $user->is_admin);
    }
}
