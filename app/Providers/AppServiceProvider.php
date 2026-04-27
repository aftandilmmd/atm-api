<?php

namespace App\Providers;

use App\Models\Transaction;
use App\Models\User;
use App\Policies\TransactionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::define('manage-atm', fn (User $user) => $user->isAdmin());
    }
}
