<?php

namespace App\Models\User;

use App\Models\Catalogos\Familia;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Contracts\Role as RoleContract;

class Role extends Model implements RoleContract{

    use HasPermissions, Auditable;
//    use SoftDeletes;

    protected $guard_name = 'web'; // or whatever guard you want to use
    protected $table = 'roles';
    protected $fillable = ['id','name','descripcion','abreviatura','color',];

    public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public static function findOrCreateRoleMasive(string $name, string $descripcion, Permission $permission_id){
        $role = static::all()->where('name', $name)->first();
        if (!$role) {
            $role = static::create([
                'name'=>$name,
                'description'=>$descripcion,
                'guard_name'=>'web',
            ]);
            $role->permissions()->attach($permission_id);
        }
        return $role;

    }


    public static function findByName(string $name, ?string $guardName = null): RoleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $role = static::findByParam(['name' => $name, 'guard_name' => $guardName]);

        if (! $role) {
            throw RoleDoesNotExist::named($name, $guardName);
        }

        return $role;
    }

    /**
     * Find a role by its id (and optionally guardName).
     *
     * @return RoleContract|\Spatie\Permission\Models\Role
     */
    public static function findById(int|string $id, ?string $guardName = null): RoleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $role = static::findByParam([(new static)->getKeyName() => $id, 'guard_name' => $guardName]);

        if (! $role) {
            throw RoleDoesNotExist::withId($id, $guardName);
        }

        return $role;
    }

    /**
     * Find or create role by its name (and optionally guardName).
     *
     * @return RoleContract|Role
     */
    public static function findOrCreate(string $name, ?string $guardName = null): RoleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $role = static::findByParam(['name' => $name, 'guard_name' => $guardName]);

        if (! $role) {
            return static::query()->create(['name' => $name, 'guard_name' => $guardName] + (app(PermissionRegistrar::class)->teams ? [app(PermissionRegistrar::class)->teamsKey => getPermissionsTeamId()] : []));
        }

        return $role;
    }

    /**
     * Finds a role based on an array of parameters.
     *
     * @return RoleContract|Role|null
     */
    protected static function findByParam(array $params = []): ?RoleContract
    {
        $query = static::query();

        if (app(PermissionRegistrar::class)->teams) {
            $teamsKey = app(PermissionRegistrar::class)->teamsKey;

            $query->where(fn ($q) => $q->whereNull($teamsKey)
                ->orWhere($teamsKey, $params[$teamsKey] ?? getPermissionsTeamId())
            );
            unset($params[$teamsKey]);
        }

        foreach ($params as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }

    public function familias(): \Illuminate\Database\Eloquent\Relations\BelongsToMany{
        return $this->belongsToMany(Familia::class, 'familia_user_role', 'role_id', 'familia_id')
            ->withPivot( 'user_id', 'tutor_id', 'vivecon_id', 'es_menor');
    }

    public function usersfamilia(): \Illuminate\Database\Eloquent\Relations\BelongsToMany{
        return $this->belongsToMany(User::class, 'familia_user_role', 'role_id', 'user_id')
            ->withPivot( 'familia_id', 'tutor_id', 'vivecon_id', 'es_menor');
    }



}
