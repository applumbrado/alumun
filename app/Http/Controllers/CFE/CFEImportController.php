<?php

namespace App\Http\Controllers\CFE;

use App\Http\Controllers\Controller;
use App\Models\Catalogos\Grupo;
use App\Models\CFE\Recibo;
use App\Services\CFE\CFEImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CFEImportController extends Controller
{

    public function index(){

        $recibos = Recibo::query()->orderByDesc('id')->get();


//        dd($recibos);

        return Inertia::render('CFE/ImportarArchivosCFE', [
            'recibos' => $recibos,
        ]);
    }

//    public function importar(Request $request, CFEImportService $service)
//    {
//        $request->validate([
//            'archivo_zip' => 'required|file|mimes:zip',
//            'forceOverwrite' => 'nullable|boolean'
//        ]);
//
//        $force = $request->forceOverwrite ?? false;
//
//        $result = $service->procesarZip(
//            $request->file('archivo_zip')->getRealPath(),
//            $force
//        );
//
//        return response()->json([
//            'success' => true,
//            'resultados' => $result
//        ]);
//    }

    public function importar(Request $request)
    {
        $archivos = [];

        // Si viene como 'archivos[]'
        if ($request->hasFile('archivos')) {
            $archivos = $request->file('archivos');
        }

        // Si viene como 'archivo_zip'
        if ($request->hasFile('archivo_zip')) {
            $archivos[] = $request->file('archivo_zip');
        }

        $request->validate([
            'forceOverwrite' => 'nullable|boolean'
        ]);

        $force = $request->forceOverwrite ?? false;


        if (empty($archivos)) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibió ningún archivo ZIP'
            ], 422);
        }

        $service = new CFEImportService();
        $resultados = [];

        foreach ($archivos as $archivo) {
            try {
                $resultados[] = $service->procesarZip($archivo, $force);
            } catch (\Throwable $th) {
                $resultados[] = [
                    'archivo' => $archivo->getClientOriginalName(),
                    'success' => false,
                    'error' => $th->getMessage()
                ];
            }
        }

        $per = periodo_vigente();

        $recibos = Recibo::with('periodo','servicio')
            ->where('periodo_id', $per->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'procesados' => $resultados,
            'recibos'    => $recibos,
        ]);

    }



}
