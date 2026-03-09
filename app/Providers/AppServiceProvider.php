<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AlertaRendimiento;
use App\Models\AlertaFondeo;
use App\Observers\AlertaRendimientoObserver;
use App\Observers\AlertaFondeoObserver;

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
        AlertaRendimiento::observe(AlertaRendimientoObserver::class);
        AlertaFondeo::observe(AlertaFondeoObserver::class);
    }
}
