<?php

namespace App\Providers;

use App\Modules\MLStandard\Models\OrcMLstd;
use App\Observers\OrcMLstdObserver;
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
        OrcMLstd::observe(OrcMLstdObserver::class);
    }
}
