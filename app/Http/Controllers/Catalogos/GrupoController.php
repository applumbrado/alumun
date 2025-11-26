<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Catalogos\Grupo;
use App\Http\Requests\Catalogos\GrupoRequest;
use Inertia\Inertia;

class GrupoController extends Controller
{
    public function index()
    {
        $grupos = Grupo::orderBy('grupo')->get();

        return Inertia::render('Catalogos/Grupos/Index', [
            'grupos' => $grupos,
        ]);
    }

    public function store(GrupoRequest $request)
    {
        Grupo::create($request->validated());

        return back()->with('success', 'Grupo creado correctamente');
    }

    public function update(GrupoRequest $request, Grupo $grupo)
    {
        $grupo->update($request->validated());

        return back()->with('success', 'Grupo actualizado');
    }

    public function destroy(Grupo $grupo)
    {
        $grupo->delete();

        return back()->with('success', 'Grupo eliminado');
    }
}
