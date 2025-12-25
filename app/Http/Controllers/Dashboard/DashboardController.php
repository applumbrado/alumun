<?php

namespace App\Http\Controllers\Dashboard;


use App\Http\Controllers\Controller;
use App\Models\Catalogos\Grupo;
use App\Models\Catalogos\Servicio;
use App\Models\CFE\ArchivoPlano;
use App\Models\CFE\Recibo;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index() {
        $u = Auth::user();
        $pv = periodo_vigente();

        $grupos = Grupo::all()->count() ?? 0;
        $servicios = Servicio::all()->count() ?? 0;
        $archivos_planos = ArchivoPlano::all()->count() ?? 0;
        $recibos_periodo_videgente = Recibo::where('periodo_id', $pv->id)->count() ?? 0;

        return Inertia::render('Dashboard', [
            'user' => [
                'username' => $u->username,
                'nombre' => $u->nombre,
                'ap_paterno' => $u->ap_paterno,
                'ap_materno' => $u->ap_materno,
                'nombre_completo' => $u->full_name,
            ],
            'stats' => [
                'reportes_pendientes' => 24,
                'luminarias_operativas' => 1304,
                'luminarias_apagadas' => 87,
                'zonas_prioritarias' => 5,
                'grupos' => $grupos,
                'servicios' => $servicios,
                'archivos_planos' => $archivos_planos,
                'recibos_periodo_videgente' => $recibos_periodo_videgente,
            ],
        ]);
    }
}
