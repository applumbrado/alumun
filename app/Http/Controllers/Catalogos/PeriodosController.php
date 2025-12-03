<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\CFE\Periodo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PeriodosController extends Controller{
    //
    public function index()
    {
        $periodos = Periodo::orderBy('ano', 'desc')
            ->orderBy('mes', 'desc')
            ->orderBy('tipo')
            ->get();

        return Inertia::render('Catalogos/Periodos/PeriodosListIndex', [
            'periodos' => $periodos,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePeriodo($request);

        // Generar ANOMES y nombre de mes si no viene
        $data['anomes'] = sprintf('%04d%02d', $data['ano'], $data['mes']);
        if (empty($data['mes_nombre'])) {
            $data['mes_nombre'] = Periodo::nombreMes($data['mes']);
        }

        $periodo = Periodo::create($data);

        if ($periodo->predeterminado) {
            $periodo->marcarComoPredeterminado();
        }

        return response()->json([
            'success' => true,
            'message' => 'Periodo creado correctamente',
            'periodo' => $periodo->fresh(),
        ], 201);
    }

    public function update(Request $request, Periodo $periodo)
    {
        $data = $this->validatePeriodo($request, $periodo->id);

        $data['anomes'] = sprintf('%04d%02d', $data['ano'], $data['mes']);
        if (empty($data['mes_nombre'])) {
            $data['mes_nombre'] = Periodo::nombreMes($data['mes']);
        }

        $periodo->update($data);

        if ($periodo->predeterminado) {
            $periodo->marcarComoPredeterminado();
        }

        return response()->json([
            'success' => true,
            'message' => 'Periodo actualizado correctamente',
            'periodo' => $periodo->fresh(),
        ]);
    }

    public function destroy(Periodo $periodo)
    {
        $periodo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Periodo eliminado correctamente',
        ]);
    }

    public function setPredeterminado(Periodo $periodo)
    {
        $periodo->marcarComoPredeterminado();

        return response()->json([
            'success' => true,
            'message' => 'Periodo marcado como predeterminado',
            'periodo' => $periodo->fresh(),
        ]);
    }

    protected function validatePeriodo(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'ano' => ['required', 'integer', 'min:2000', 'max:2100'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
            'mes_nombre' => ['nullable', 'string', 'max:20'],
            'tipo' => ['required', 'integer', 'min:0', 'max:9'],
            'digito' => ['nullable', 'integer', 'min:0', 'max:9'],
            'predeterminado' => ['boolean'],
        ]);
    }
}
