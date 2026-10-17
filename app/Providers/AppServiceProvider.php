<?php

namespace App\Providers;

use App\Repositories\Contracts\CashAdvanceRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\PayrollRepositoryInterface;
use App\Repositories\Contracts\ShopRepositoryInterface;
use App\Repositories\Contracts\SpkRepositoryInterface;
use App\Repositories\Eloquent\CashAdvanceRepository;
use App\Repositories\Eloquent\EmployeeRepository;
use App\Repositories\Eloquent\PayrollRepository;
use App\Repositories\Eloquent\ShopRepository;
use App\Repositories\Eloquent\SpkRepository;
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
        $this->app->bind(SpkRepositoryInterface::class, SpkRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(ShopRepositoryInterface::class, ShopRepository::class);
        $this->app->bind(CashAdvanceRepositoryInterface::class, CashAdvanceRepository::class);
        $this->app->bind(PayrollRepositoryInterface::class, PayrollRepository::class);
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
