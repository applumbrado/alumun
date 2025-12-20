<?php

namespace App\Providers;

use App\Models\CFE\Periodo;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void{

        require_once app_path('Helpers/CfeXmlHelpers.php');
        require_once app_path('Helpers/CfeXmlCI.php');

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void{

        Vite::prefetch(concurrency: 3);

        if($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Inertia::share('periodo_vigente', function () {

            $p = Periodo::vigente();

            if (! $p) {
                return null;
            }

            return [
                'id'             => $p->id,
                'anomes'         => $p->anomes,
                'ano'            => $p->ano,
                'mes'            => $p->mes,
                'mes_nombre'     => $p->mes_nombre,
                'tipo'           => $p->tipo,
                'digito'         => $p->digito,
                'label'          => $p->label,
                'anio_mes'       => $p->anio_mes,
                'predeterminado' => $p->predeterminado,
            ];

        });


    }
}
