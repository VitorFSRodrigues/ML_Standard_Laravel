<?php

namespace App\Providers;
use App\Models\OrcMLstd;
use App\Models\Triagem;
use App\Observers\OrcMLstdObserver;
use App\Observers\TriagemObserver;

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
        Triagem::observe(TriagemObserver::class);
        OrcMLstd::observe(OrcMLstdObserver::class);
    }
}
