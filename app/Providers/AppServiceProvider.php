<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Repositories\Contracts\SpkRepositoryInterface::class, \App\Repositories\Eloquent\SpkRepository::class);
        $this->app->bind(\App\Repositories\Contracts\EmployeeRepositoryInterface::class, \App\Repositories\Eloquent\EmployeeRepository::class);
        $this->app->bind(\App\Repositories\Contracts\ShopRepositoryInterface::class, \App\Repositories\Eloquent\ShopRepository::class);
        $this->app->bind(\App\Repositories\Contracts\CashAdvanceRepositoryInterface::class, \App\Repositories\Eloquent\CashAdvanceRepository::class);
        $this->app->bind(\App\Repositories\Contracts\PayrollRepositoryInterface::class, \App\Repositories\Eloquent\PayrollRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate limiter login: 5 percobaan/menit per kombinasi username+IP,
        // dan 20 percobaan/menit per IP untuk mencegah penyebaran username.
        RateLimiter::for('login', function (Request $request) {
            $username = Str::lower((string) $request->input('username'));

            return [
                Limit::perMinute(5)->by($username.'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });
    }
}
