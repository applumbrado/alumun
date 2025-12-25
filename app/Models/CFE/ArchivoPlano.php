<?php

namespace App\Models\CFE;

use App\Models\Catalogos\Grupo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ArchivoPlano extends Model
{
    use SoftDeletes;

    protected $table = 'archivosplanos';

    protected $fillable = [
        'periodo_id',
        'grupo_id',
        'consecutivo',
        'original_name',
        'stored_name',
        'disk',
        'path',
        'size',
        'mime',
        'user_id',
    ];

    protected $casts = [
        'periodo_id' => 'integer',
        'grupo_id'   => 'integer',
        'consecutivo'=> 'integer',
        'size'       => 'integer',
        'user_id'    => 'integer',
    ];

    public function periodo(){
        return $this->belongsTo(Periodo::class);
    }

    public function grupo(){
        return $this->belongsTo(Grupo::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }



    public function getUrlAttribute(): ?string
    {
        try {
            return Storage::disk($this->disk)->url($this->path);
        } catch (\Throwable $e) {
            return null;
        }
    }

}
