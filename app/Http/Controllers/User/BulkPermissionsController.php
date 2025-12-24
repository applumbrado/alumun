<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;
class BulkPermissionsController extends Controller{

    public function edit(){

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

//        $allDatas = Permission::query()->select('id', 'name as data')->get()->toArray();
        $allDatas = Permission::select('id', 'name as data')
            ->whereNotIn('name', ['all', 'sysop'])
            ->get()
            ->toArray();

        return Inertia::render('Users/Asignaciones/BulkUserEntitiesAssignment', [
            'users' => $users,
            'allDatas' => $allDatas,
            'assignedDatasByUser' => $this->getUserPermissionsList(),
            'addUrl' => '/bulk-permisos/assign-partial',
            'removeUrl' => '/bulk-permisos/remove-partial',
            'tableName' => 'Permisos',
        ]);

    }


    /**
     * Asigna (parcialmente) datas a un usuario, sin quitar datas previos.
     */
    public function assignPartial(Request $request){

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'datas'   => 'required|array',
            'datas.*' => 'exists:permissions,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->permissions()->syncWithoutDetaching($validated['datas']);

        return response()->json([
            'message' => 'Permisos removidos correctamente',
            'data' => $this->getUserPermissionsList(),
        ], 200);


    }

    /**
     * Remueve (parcialmente) datas de un usuario.
     */
    public function removePartial(Request $request){

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'datas'   => 'required|array',
            'datas.*' => 'exists:permissions,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $datasToDetach = array_map('intval', $validated['datas']);
        $user->permissions()->detach($datasToDetach);

        // Sin redirect, simplemente JSON:
        return response()->json([
            'message' => 'Permisos removidos correctamente',
            'data' => $this->getUserPermissionsList(),
        ], 200);

    }


    public function getUserPermissionsList(){
        $users = User::with('permissions:id,name')->get(['id']);
        $assignedDatasByUser = [];

        foreach ($users as $user) {
            // Asegurar que cada item tenga 'id' no 'data_id'
            $assignedDatasByUser[$user->id] = $user->permissions->map(function ($permission) {
                return ['id' => $permission->id, 'name' => $permission->name];
            })->toArray();
        }

        return $assignedDatasByUser;
    }

}
