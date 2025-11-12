<?php

namespace App\Providers;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
//use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BroadcastManager::class, fn ($app) => new BroadcastManager($app));

        $this->app->singleton(BroadcasterContract::class, function ($app) {
            return $app->make(BroadcastManager::class)->connection();
        });

        $this->app->alias(
            BroadcastManager::class, BroadcastingFactory::class
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
//    public function provides()
//    {
//        return [
//            BroadcastManager::class,
//            BroadcastingFactory::class,
//            BroadcasterContract::class,
//        ];
//    }

    public function boot(): void
    {
        // Asegúrate de que esta línea esté activa (descomentada)
        // Esto registra la ruta POST /broadcasting/auth
        Broadcast::routes();

        // Carga las definiciones de tus canales
        require base_path('routes/channels.php');
    }
}
