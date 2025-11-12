<?php

namespace App\Models\User;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class UserDataExtend extends Model{

    use Notifiable, SoftDeletes, Auditable;

    protected $guard_name = 'web'; // or whatever guard you want to use
    protected $table = 'user_extend';

    protected $fillable = [
        'id','user_id',
        'lugar_nacimiento','ocupacion','profesion','lugar_trabajo','nacionalidad',
    ];
    protected $hidden = ['deleted_at','created_at','updated_at'];


}
