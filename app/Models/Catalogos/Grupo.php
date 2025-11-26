<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grupo extends Model
{
    use SoftDeletes;

    protected $table = 'grupos';

    protected $fillable = [
        'grupo',
        'clave',
    ];

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'grupo_id');
    }
}
