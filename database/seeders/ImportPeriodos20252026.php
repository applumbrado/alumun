<?php

namespace Database\Seeders;

use App\Models\CFE\Periodo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ImportPeriodos20252026 extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void{

// --- Periodos de 2025 ---
        Periodo::create(['anomes'=>'202509', 'ano'=>2025, 'mes'=>9, 'mes_nombre'=>'SEPTIEMBRE',]);
        Periodo::create(['anomes'=>'202510', 'ano'=>2025, 'mes'=>10, 'mes_nombre'=>'OCTUBRE','predeterminado'=>true]);
        Periodo::create(['anomes'=>'202511', 'ano'=>2025, 'mes'=>11, 'mes_nombre'=>'NOVIEMBRE',]);
        Periodo::create(['anomes'=>'202512', 'ano'=>2025, 'mes'=>12, 'mes_nombre'=>'DICIEMBRE',]);

// --- Periodos de 2026 ---
        Periodo::create(['anomes'=>'202601', 'ano'=>2026, 'mes'=>1, 'mes_nombre'=>'ENERO',]);
        Periodo::create(['anomes'=>'202602', 'ano'=>2026, 'mes'=>2, 'mes_nombre'=>'FEBRERO',]);
        Periodo::create(['anomes'=>'202603', 'ano'=>2026, 'mes'=>3, 'mes_nombre'=>'MARZO',]);
        Periodo::create(['anomes'=>'202604', 'ano'=>2026, 'mes'=>4, 'mes_nombre'=>'ABRIL',]);
        Periodo::create(['anomes'=>'202605', 'ano'=>2026, 'mes'=>5, 'mes_nombre'=>'MAYO',]);
        Periodo::create(['anomes'=>'202606', 'ano'=>2026, 'mes'=>6, 'mes_nombre'=>'JUNIO',]);
        Periodo::create(['anomes'=>'202607', 'ano'=>2026, 'mes'=>7, 'mes_nombre'=>'JULIO',]);
        Periodo::create(['anomes'=>'202608', 'ano'=>2026, 'mes'=>8, 'mes_nombre'=>'AGOSTO',]);
        Periodo::create(['anomes'=>'202609', 'ano'=>2026, 'mes'=>9, 'mes_nombre'=>'SEPTIEMBRE',]);
        Periodo::create(['anomes'=>'202610', 'ano'=>2026, 'mes'=>10, 'mes_nombre'=>'OCTUBRE',]);
        Periodo::create(['anomes'=>'202611', 'ano'=>2026, 'mes'=>11, 'mes_nombre'=>'NOVIEMBRE',]);
        Periodo::create(['anomes'=>'202612', 'ano'=>2026, 'mes'=>12, 'mes_nombre'=>'DICIEMBRE',]);
    }
}
