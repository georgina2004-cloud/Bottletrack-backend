<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\DetalleVenta;
use App\Observers\DetalleVentaObserver;
use App\Models\DetalleCompra;
use App\Observers\DetalleCompraObserver;

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
        DetalleVenta::observe(DetalleVentaObserver::class);
        DetalleCompra::observe(DetalleCompraObserver::class);

        if ($this->app->environment('production')) {
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }
    }
}
