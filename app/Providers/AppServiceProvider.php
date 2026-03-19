<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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
        Schema::defaultStringLength(191);

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            [\App\Listeners\UserActivityListener::class, 'handleLogin']
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            [\App\Listeners\UserActivityListener::class, 'handleLogout']
        );
    }
}
