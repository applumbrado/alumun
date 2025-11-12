<?php

namespace App\Models\User;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class UserAlumno extends Model{
    use Notifiable, SoftDeletes, Auditable;

    protected $guard_name = 'web'; // or whatever guard you want to use
    protected $table = 'user_alumno';

    protected $fillable = [
        'id','user_id',
        'matricula_interna','matricula_oficial','email_alumno',
        'enfermedades','reacciones_alergicas','tipo_sangre',
        'beca_sep',
        'beca_arji',
        'beca_sp',
        'beca_bach',
        'es_baja','motivo_baja',
        'fecha_ingreso','observaciones',
    ];
    protected $casts = [
        'es_baja' => 'boolean',
        'fecha_ingreso' => 'date:Y-m-d',
    ];


    protected $hidden = ['deleted_at','created_at','updated_at'];

    public function setBecaSepAttribute($value){
        $this->attributes['beca_sep'] = $value ?? 0;
    }

    public function setBecaArjiAttribute($value){
        $this->attributes['beca_arji'] = $value ?? 0;
    }

    public function setBecaSpAttribute($value){
        $this->attributes['beca_sp'] = $value ?? 0;
    }

    public function setBecaBachAttribute($value){
        $this->attributes['beca_bach'] = $value ?? 0;
    }

//    protected static function booted()
//    {
//        static::saving(function ($model) {
//            if (is_null($model->beca_sep)) {
//                $model->beca_sep = 0;
//            }
//            if (is_null($model->beca_arji)) {
//                $model->beca_arji = 0;
//            }
//            if (is_null($model->beca_sp)) {
//                $model->beca_sp = 0;
//            }
//            if (is_null($model->beca_bach)) {
//                $model->beca_bach = 0;
//            }
//        });
//    }


}
