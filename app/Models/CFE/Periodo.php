<?php

namespace App\Models\CFE;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Periodo extends Model{

    use SoftDeletes;

    protected $table = 'periodos';

    protected $fillable = [
        'id',
        'anomes',
        'ano',
        'mes',
        'mes_nombre',
        'tipo',
        'digito',

    ];


}
