<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Catalogos\Servicio;
use App\Models\Catalogos\Grupo;
use App\Http\Requests\Catalogos\ServicioRequest;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ServicioController extends Controller
{
    public function index()
    {
        return Inertia::render('Catalogos/Servicios/Index', [
            'servicios' => Servicio::with('grupo')->orderBy('rpu')->get(),
            'grupos' => Grupo::orderBy('grupo')->get(),
        ]);
    }

    public function store(ServicioRequest $request)
    {
        Servicio::create($request->validated());

        return back()->with('success', 'Servicio creado correctamente');
    }

    public function update(ServicioRequest $request, Servicio $servicio)
    {
        $datas = $request->only([
            'rpu','medidor','cuenta','tarifa',
            'carga_contratada','carga_conectada','rmu',
            'carga_minima', 'carga_maxima',
            'direccion','ciudad','colonia','calle_1','calle_2','calle_3',
            'alias','grupo_id']);

        $datas['alias'] = $datas['alias'] ?? '';
        $datas['calle_2'] = $datas['calle_2'] ?? '';
        $datas['calle_3'] = $datas['calle_3'] ?? '';

        $servicio->update($datas);

        return back()->with('success', 'Servicio actualizado');
    }

    public function destroy(Servicio $servicio)
    {
        $servicio->delete();

        return back()->with('success', 'Servicio eliminado');
    }
}
