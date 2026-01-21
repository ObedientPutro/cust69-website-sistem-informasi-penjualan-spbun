<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Gate: IS OWNER (Super Admin)
        Gate::define('access-owner', function (User $user) {
            return $user->role === RoleEnum::OWNER;
        });

        // Gate: IS OPERATOR
        Gate::define('access-operator', function (User $user) {
            return $user->role === RoleEnum::OPERATOR;
        });

        // Gate: General Access (Bisa login ke dashboard)
        // Berguna jika nanti ada user 'inactive'
        Gate::define('access-dashboard', function (User $user) {
            return in_array($user->role, [RoleEnum::OWNER, RoleEnum::OPERATOR]);
        });
    }
}
