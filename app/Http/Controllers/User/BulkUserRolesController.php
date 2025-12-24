<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

use App\Models\User\Permission;
use App\Models\User\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BulkUserRolesController extends Controller
{
    public function edit(){

//        $users = User::query()
//            ->orderBy('ap_paterno')
//            ->orderBy('ap_materno')
//            ->orderBy('nombre')
//            ->get()
//            ->toArray();

        if ( ! Auth::user()->hasRole('Admin') ) {
            $users = User::query()
                ->whereDoesntHave('permissions', function (Builder $query) {
                    $query->whereIn('name', ['all', 'sysop']);
                })
                ->whereDoesntHave('roles', function (Builder $query) {
                    $query->whereIn('name', ['Admin', 'SysOp']);
                })
                ->orderBy('ap_paterno')
                ->orderBy('ap_materno')
                ->orderBy('nombre')
                ->get()
                ->toArray();
        }else{
            $users = User::query()
                ->orderBy('ap_paterno')
                ->orderBy('ap_materno')
                ->orderBy('nombre')
                ->get()
                ->toArray();
        }


//        $allDatas = Role::query()
//            ->select('id', 'name as data')
//            ->whereNotIn('name', ['Admin','Administrator','Administrador'])
//            ->get()->toArray();

        $allDatas = Role::select('id', 'name as data')
            ->whereNotIn('name', ['Admin', 'SysOp','Administrator','Administrador'])
            ->get()
            ->toArray();


        return Inertia::render('Users/Asignaciones/BulkUserEntitiesAssignment', [
            'users' => $users,
            'allDatas' => $allDatas,
            'assignedDatasByUser' => $this->getUserRolesList(),
            'addUrl' => '/bulk-roles/assign-partial',
            'removeUrl' => '/bulk-roles/remove-partial',
            'tableName' => 'Roles',
        ]);

    }


    /**
     * Asigna (parcialmente) datas a un usuario, sin quitar datas previos.
     */
    public function assignPartial(Request $request){

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'datas'   => 'required|array',
            'datas.*' => 'exists:roles,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $role_id = $validated['datas'][0];
        $role = Role::find($role_id);
        if($role->name !== 'Admin' && $role->name !== 'Administrador' && $role->name !== 'Administrator'){
            $user->roles()->syncWithoutDetaching($validated['datas']);
        }


        return response()->json([
            'message' => 'Roles removidos correctamente',
            'data' => $this->getUserRolesList(),
        ], 200);


    }

    /**
     * Remueve (parcialmente) datas de un usuario.
     */
    public function removePartial(Request $request){

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'datas'   => 'required|array',
            'datas.*' => 'exists:roles,id',
        ]);

//        dd($validated['datas']);

        $user = User::findOrFail($validated['user_id']);
        $datasToDetach = array_map('intval', $validated['datas']);
        $user->roles()->detach($datasToDetach);

//        dd( $this->getUserRolesList() );

        // Sin redirect, simplemente JSON:
        return response()->json([
            'message' => 'Roles removidos correctamente',
            'data' => $this->getUserRolesList(),
        ], 200);

    }


    public function getUserRolesList(){
        $users = User::with('roles:id,name')->get(['id']);
        $assignedDatasByUser = [];

        foreach ($users as $user) {
            // Asegurar que cada item tenga 'id' no 'data_id'
            $assignedDatasByUser[$user->id] = $user->roles->map(function ($role) {
                return ['id' => $role->id, 'name' => $role->name];
            })->toArray();
        }

        return $assignedDatasByUser;
    }

}
