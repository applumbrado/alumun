<?php

namespace App\Events;

use App\Models\CFE\Periodo;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/*
 *
 *
 * ShouldBroadcast → requiere queue worker. php artisan queue:work redis

   ShouldBroadcastNow → no requiere queue, por eso “funciona solo”.
 *
 */
class PeriodoVigenteChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $periodo;

    public function __construct(Periodo $periodo)
    {
        $this->periodo = [
            'id'             => $periodo->id,
            'anomes'         => $periodo->anomes,
            'ano'            => $periodo->ano,
            'mes'            => $periodo->mes,
            'mes_nombre'     => $periodo->mes_nombre,
            'tipo'           => $periodo->tipo,
            'digito'         => $periodo->digito,
            'label'          => $periodo->label,
            'anio_mes'       => $periodo->anio_mes,
            'predeterminado' => $periodo->predeterminado,
        ];
    }

    public function broadcastOn(){
        // Canal público
        return new Channel('alumun.periodos');
    }

    public function broadcastAs(){
        return 'PeriodoVigenteChanged';
    }
}
