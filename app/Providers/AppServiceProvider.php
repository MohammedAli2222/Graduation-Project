<?php

namespace App\Providers;

use App\Models\CaseType;
use App\Models\Category;
use App\Models\Department;
use App\Models\Group;
use App\Observers\CaseTypeObserver;
use App\Observers\CategoryObserver;
use App\Observers\DepartmentObserver;
use App\Observers\GroupObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        CaseType::observe(CaseTypeObserver::class);
        Group::observe(GroupObserver::class);
        Category::observe(CategoryObserver::class);
        Department::observe(DepartmentObserver::class);

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
