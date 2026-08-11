<?php

namespace App\Providers;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Pusher\Pusher;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('pusher', function () {
            return new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                [
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'useTLS' => true,
                ]
            );
        });
    }

     public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}