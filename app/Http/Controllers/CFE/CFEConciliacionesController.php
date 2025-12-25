<?php

namespace App\Http\Controllers\CFE;

use App\Http\Controllers\Controller;
use App\Models\CFE\Periodo;
use App\Models\CFE\Recibo;
use App\Services\CFE\CFEConciliacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CFEConciliacionesController extends Controller
{
    public function index()
    {
        // ✅ periodo vigente (ajusta al campo real: predeterminado / vigente / actual)
        $periodo = Periodo::query()
            ->where('predeterminado', true)
            ->firstOrFail();

        $anio = (int) $periodo->ano;
        $mes2 = str_pad((string)$periodo->mes, 2, '0', STR_PAD_LEFT);

        $folder = "cfe/{$anio}/{$mes2}/expediente/archivos_planos";

        $files = collect(Storage::disk('public')->files($folder))
            ->filter(fn($p) => str_ends_with(strtolower($p), '.xlsx'))
            ->map(fn($p) => ['path' => $p, 'name' => basename($p)])
            ->values()
            ->all();

        $stats = [
            'recibos' => Recibo::where('periodo_id', $periodo->id)->count(),
            'validados' => Recibo::where('periodo_id', $periodo->id)->where('validado', true)->count(),
            'no_validados' => Recibo::where('periodo_id', $periodo->id)->where('validado', false)->count(),
        ];

        return Inertia::render('CFE/Conciliaciones/Index', [
            'periodo' => $periodo,
            'folder' => $folder,
            'files' => $files,
            'stats' => $stats,
            'runUrl' => route('cfe.conciliaciones.run'),
            'itemsUrl' => route('cfe.conciliaciones.items'),
        ]);
    }

    public function run(CFEConciliacionService $service)
    {
        $periodo = Periodo::query()
            ->where('predeterminado', true)
            ->firstOrFail();

        $result = $service->conciliarPeriodoVigente($periodo);

        return response()->json($result);
    }

    public function items(Request $request)
    {
        $periodo = Periodo::query()
            ->where('predeterminado', true)
            ->firstOrFail();

        $validated = $request->query('validated'); // '1' | '0' | null
        $q = trim((string)$request->query('q', ''));
        $perPage = (int) $request->query('per_page', 1000);
        $perPage = max(10, min($perPage, 10000));

        $query = Recibo::query()
            ->where('periodo_id', $periodo->id);

        if ($validated === '1') $query->where('validado', true);
        if ($validated === '0') $query->where('validado', false);

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('rpu', 'like', "%{$q}%")
                    ->orWhere('periodo', 'like', "%{$q}%");
            });
        }

        $p = $query
            ->select([
                'id','rpu','periodo','total','consumo','desde','hasta','validado',
                'rpu_ok','periodo_ok','total_ok','consumo_ok','desde_ok','hasta_ok',
                'conciliado_at',
            ])
            ->orderByDesc('validado')     // validados primero
            ->orderBy('rpu')
            ->paginate($perPage);

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
