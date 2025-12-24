<?php

namespace App\Http\Controllers;

use App\Classes\FuncionesController;
use App\Http\Requests\User\UserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller{
    Use SoftDeletes;

    public function index(Request $request): \Inertia\Response{

        $filters =$request->input('search');

        if ($filters) {
            $F           = new FuncionesController();
            $filters      = strtolower($filters);
            $filters      = $F->str_sanitizer($filters);
            $tsString     = $F->string_to_tsQuery( strtoupper($filters),' & ');

//            ->whereHas('roles', function ($query) {
//                return $query->whereNot('name', 'Administrator');
//            })
            $users = User::with(['user_address', 'user_data_extend'])
                ->search( $tsString )
                ->where('id','>',1)
                ->orderBy('id', 'desc')
                ->paginate();

            return Inertia::render('Users/Index', [
                'users' => $users,
                'totalUsuarios' => User::count(),
                'tipo_usuario' => 0,
            ]);
        }else{
//            ->orWhereHas('roles', function ($query) {
//                return $query->whereNot('name', 'Administrator');
//            })
            $user = User::with(['user_address', 'user_data_extend'])
                ->where('id','>',1)
                ->orderBy('id', 'desc')
                ->paginate(250);
        }

        return Inertia::render('Users/Index', [
            'users' => $user,
            'totalUsuarios' => User::count(),
            'tipo_usuario' => 0,
        ]);

    }

    public function store(UserRequest $request){
        $request->managed(null);
return redirect()->back()->with('success', 'Usuario creado exitosamente');
    }

    public function update(UserRequest $request, User $user){
        $User = $request->managed($user);
        return redirect()->back()->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy(User $user){
        $user->findOrFail($user->id);

        $user->forceDelete();
        return redirect()->back()->with('success', 'Usuario eliminado exitosamente');
    }


}
