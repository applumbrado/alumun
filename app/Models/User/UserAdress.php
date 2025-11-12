<?php

namespace App\Models\User;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class UserAdress extends Model{

    use Notifiable, SoftDeletes, Auditable;

    protected $guard_name = 'web'; // or whatever guard you want to use
    protected $table = 'user_address';

    protected $fillable = [
        'id','user_id',
        'calle','num_ext','num_int',
        'colonia','localidad','municipio','estado','pais','cp',
    ];

    protected $hidden = ['deleted_at','created_at','updated_at'];


}
