<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
   public function register(): void
    {
        $this->app->singleton(\App\Services\CalculadoraComision::class, fn () => new \App\Services\CalculadoraComision([
            new \App\Services\Comisiones\ComisionTarjeta(),
            new \App\Services\Comisiones\ComisionTransferencia(),
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
