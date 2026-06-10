<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        RateLimiter::for('strict_auth', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip())->response(function () {
                return response_error(
                    null,
                    429,
                    'Too many attempts. You are allowed only 3 attempts per minute. Please wait a minute and try again'
                );
            });
        });
    }
}
