<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Catalogos\Ciclo;
use App\Models\Catalogos\Familia;
use App\Models\Catalogos\Grupo;
use App\Models\Catalogos\Nivel;
use App\Models\User\Permission;
use App\Models\User\Role;
use App\Models\User\UserAdress;
use App\Models\User\UserAlumno;
use App\Models\User\UserDataExtend;
use App\Traits\Auditable;
use App\Traits\UserAttributes;
use App\Traits\UserImport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use LaravelAndVueJS\Traits\LaravelPermissionToVueJS;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable{

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasPermissions, LaravelPermissionToVueJS;

    use UserImport, UserAttributes, SoftDeletes;
    use HasApiTokens, HasFactory, Notifiable, Auditable;

    protected $guard_name = 'web';
    protected $table = 'users';

    protected $appends = [
        'full_name','full_name_with_username','path_image_profile','path_image_thumb_profile',
        'path_image_p_n_g_profile'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'username', 'email', 'password',
        'nombre','ap_paterno','ap_materno',
        'curp','emails','celulares','telefonos',
        'fecha_nacimiento','genero',
        'root','filename','filename_png','filename_thumb',
        'empresa_id','ip','host','searchtext_user',
        'logged','logged_at','logout_at', 'user_mig_id','email_verified_at',
        'creadopor_id','created_at','modipor_id','updated_at',
        'ubicacion_id','imagen_id','session_id',
        'uuid','activo','empresa_id',
    ];
//'creadopor_id','modificadopor_id',

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array{
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'fecha_nacimiento' => 'date:Y-m-d'
        ];
    }


    public function scopeSearch($query, $search){
        if (!$search || $search == "" || $search == null) return $query;
        return $query->whereRaw("searchtext_user @@ to_tsquery('spanish', ?)", [$search])
            ->orderByRaw("ts_rank(searchtext_user, to_tsquery('spanish', ?)) DESC", [$search]);
    }

//    public function scopeFilterBy($query, $filters){
//        return (new UserFilter())->applyTo($query, $filters);
//    }

    public function permisos() {
        return $this->belongsToMany(Permission::class);
    }

    public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function familias(): \Illuminate\Database\Eloquent\Relations\BelongsToMany{
        return $this->belongsToMany(Familia::class, 'familia_user_role', 'user_id', 'familia_id')
            ->withPivot( 'role_id', 'tutor_id', 'vivecon_id', 'es_menor');
    }

    public function rolesfamilias(): \Illuminate\Database\Eloquent\Relations\BelongsToMany{
        return $this->belongsToMany(Role::class, 'familia_user_role', 'user_id', 'role_id')
            ->withPivot( 'familia_id', 'tutor_id', 'vivecon_id', 'es_menor');
    }


    public function user_address(){
        return $this->hasOne(UserAdress::class);
    }

    public function user_data_extend(){
        return $this->hasOne(UserDataExtend::class);
    }

//    public function user_alumno(){
//        return $this->hasOne(UserAlumno::class);
//    }

    public function user_alumno(){
        return $this->hasOne(UserAlumno::class, 'user_id')->withDefault([
            'beca_sep' => 0,
            'beca_arji' => 0,
            'beca_sp' => 0,
            'beca_bach' => 0,
        ]);
    }

    public function IsEmptyPhoto(){
        return $this->filename == '';
    }

    public function IsFemale(){
        return $this->genero == 0;
    }

    public function scopeMyID(){
        return $this->id;
    }

    public function grupos(): BelongsToMany{
        $ciclo_predeterminado_id = Ciclo::select('id')->where('predeterminado', 1)->first()->id;
        return $this->belongsToMany(Grupo::class, 'alumnos_grupos', 'alumno_user_id', 'grupo_id')
            ->withPivot(['ciclo_id'])->wherePivot('ciclo_id', $ciclo_predeterminado_id);
    }

    public function grupo(): BelongsTo{
        $ciclo_predeterminado_id = Ciclo::select('id')->where('predeterminado', 1)->first()->id;
        return $this->belongsTo(Grupo::class, 'alumnos_grupos', 'alumno_user_id', 'grupo_id')
            ->withPivot(['ciclo_id'])->wherePivot('ciclo_id', $ciclo_predeterminado_id);
    }

    public function ciclos(): BelongsToMany{
        return $this->belongsToMany(
            Ciclo::class,
            'alumnos_grupos',
            'alumno_user_id',
            'ciclo_id'
        )->withPivot( ['grupo_id', 'is_visible', 'old_grupo_nivel_id', 'old_ciclo_id', 'old_nivel_id', 'old_grupo_id']);
    }



}
