<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Servicio extends Model
{
    use SoftDeletes;

    protected $table = 'servicios';

    protected $fillable = [
        'rpu',
        'medidor',
        'cuenta',
        'tarifa',
        'carga_contratada',
        'carga_conectada',
        'carga_minima',
        'carga_maxima',
        'rmu',
        'direccion',
        'ciudad',
        'colonia',
        'calle_1',
        'calle_2',
        'calle_3',
        'alias',
        'grupo_id'
    ];

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }
}
