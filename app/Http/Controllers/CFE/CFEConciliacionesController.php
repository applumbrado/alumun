<?php

namespace App\Http\Controllers\CFE;

use App\Http\Controllers\Controller;
use App\Models\CFE\Periodo;
use App\Models\CFE\Recibo;
use App\Services\CFE\CFEConciliacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CFEConciliacionesController extends Controller{

    public function index(Request $request)
    {
        /** @var Periodo|null $periodo */
        $periodo = periodo_vigente();
        if (!$periodo) abort(500, 'No hay periodo vigente definido.');

        $anio = (int) $periodo->ano;
        $mes  = str_pad((string)$periodo->mes, 2, '0', STR_PAD_LEFT);

        // ✅ Carpeta pública de archivos planos
        $dir = "cfe/{$anio}/{$mes}/expediente/archivos_planos";

        // ✅ Archivos XLSX detectados
        $files = collect(Storage::disk('public')->files($dir))
            ->filter(fn($p) => str_ends_with(strtolower($p), '.xlsx'))
            ->map(fn($p) => [
                'name' => basename($p),
                'path' => $p,
            ])
            ->values()
            ->all();

        // ✅ Stats rápidas del periodo vigente
        $recibosCount = Recibo::where('periodo_id', $periodo->id)->count();
        $validados    = Recibo::where('periodo_id', $periodo->id)->where('validado', true)->count();
        $noValidados  = max(0, $recibosCount - $validados);

        return Inertia::render('CFE/Conciliaciones/Index', [
            'periodo' => [
                'id' => $periodo->id,
                'ano' => $anio,
                'mes' => (int)$periodo->mes,
                'label' => "{$mes}/{$anio}",
            ],

            // ✅ Props que tu Vue YA USA
            'folder' => $dir,
            'files'  => $files,
            'stats'  => [
                'recibos' => $recibosCount,
                'validados' => $validados,
                'no_validados' => $noValidados,
            ],
            'runUrl'   => route('cfe.conciliaciones.run'),
            'itemsUrl' => route('cfe.conciliaciones.items'),

            // si quieres conservarlo para debug/flash:
            'report' => session('conciliacion_report'),
        ]);
    }



    public function run(Request $request, CFEConciliacionService $service)
    {
        /** @var Periodo|null $periodo */
        $periodo = periodo_vigente();
        if (!$periodo) {
            return back()->with('error', 'No hay periodo vigente definido.');
        }

        $result = $service->conciliarPeriodoVigente($periodo);

        //return back()->with('conciliacion_report', $report);
        return response()->json($result);

    }

    public function items(Request $request)
    {
        /** @var Periodo|null $periodo */
        $periodo = periodo_vigente();
        if (!$periodo) {
            return response()->json(['ok' => false, 'message' => 'No hay periodo vigente.'], 500);
        }

        $q        = trim((string)$request->get('q', ''));
        $status   = (string)$request->get('status', 'all');
        $validated= $request->get('validated', null);
        $perPage  = (int)$request->get('per_page', 25);
        $perPage  = max(1, min($perPage, 2000));

        $query = Recibo::query()->where('periodo_id', $periodo->id);

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('rpu', 'like', "%{$q}%")
                    ->orWhere('periodo', 'like', "%{$q}%");
            });
        }

        // ✅ Mantener tu filtro anterior (si viene)
        if ($validated === '1') {
            $query->where('validado', true);
        } elseif ($validated === '0') {
            $query->where('validado', false);
        }

        // ✅ Aceptar BOTH: los valores del front y los tuyos viejos
        // Front: validated | mismatch | no_xlsx | dup_db
        // Viejo: validado  | mismatch | no_archivo | duplicado_db
        if (in_array($status, ['validated', 'validado'], true)) {
            $query->where('validado', true);
        } elseif ($status === 'mismatch') {
            $query->where('validado', false)->whereNotNull('conciliado_at');
        } elseif (in_array($status, ['no_xlsx', 'no_archivo'], true)) {
            $query->where('validado', false)->whereNull('conciliado_at');
        } elseif (in_array($status, ['dup_db', 'duplicado_db'], true)) {
            $query->where('observaciones', 'like', 'RPU duplicado en BD%');
        }

        $p = $query
            ->select([
                'id','rpu','periodo','total','consumo','desde','hasta',
                'rpu_ok','periodo_ok','total_ok','consumo_ok','desde_ok','hasta_ok',
                'validado','conciliado_at','observaciones',
            ])
            ->orderBy('rpu')
            ->paginate($perPage)
            ->appends($request->only(['q','status','validated','per_page']));

        return response()->json([
            'ok' => true,
            'data' => $p->items(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'last_page' => $p->lastPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'from' => $p->firstItem(),
                'to' => $p->lastItem(),
            ],
        ]);
    }



}
