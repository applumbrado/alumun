<?php

namespace App\Providers;

use App\Events\EstadoDeCuenta\GenerarEstadosCuentaEvent;
use App\Listeners\EstadoDeCuenta\HandleGenerarEstadosCuentaListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

    protected $listen = [
        GenerarEstadosCuentaEvent::class => [
//            HandleGenerarEstadosCuentaListener::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
