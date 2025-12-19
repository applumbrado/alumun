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
        Periodo::create(['periodo'=>'202509', 'ano'=>2025, 'mes'=>9, 'mes_nombre'=>'SEPTIEMBRE',]);
        Periodo::create(['periodo'=>'202510', 'ano'=>2025, 'mes'=>10, 'mes_nombre'=>'OCTUBRE','predeterminado'=>true]);
        Periodo::create(['periodo'=>'202511', 'ano'=>2025, 'mes'=>11, 'mes_nombre'=>'NOVIEMBRE',]);
        Periodo::create(['periodo'=>'202512', 'ano'=>2025, 'mes'=>12, 'mes_nombre'=>'DICIEMBRE',]);

    }
}
