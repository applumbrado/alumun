<?php

namespace App\Http\Controllers\Dashboard;


use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index() {
        $u = Auth::user();
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
            ],
        ]);
    }
}
