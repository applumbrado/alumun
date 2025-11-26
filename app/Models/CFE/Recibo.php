<?php

namespace App\Models\CFE;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recibo extends Model{

    use SoftDeletes;

    protected $table = 'recibos';

    protected $fillable = [
        'id',
        'rpu','medidor','cuenta','tarifa','periodo','direccion',
        'subtotal','iva','total','periodo_id','servicio_id',
    ];


}
