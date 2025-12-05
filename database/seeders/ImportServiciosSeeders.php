<?php

namespace Database\Seeders;

use App\Classes\FuncionesController;
use App\Models\Catalogos\Servicio;
use App\Models\User;
use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportServiciosSeeders extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void{

        $urls = [
            'otros/2025-10_M0HO_ArchivoPlano.xlsx',
            'otros/2025-10_M0HP_ArchivoPlano.xlsx',
            'otros/2025-10_M0HQ_ArchivoPlano.xlsx'
        ];

        foreach ($urls as $path) {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            foreach ($rows as $index => $row) {
                try {

                    if ($index === 1) continue;

                    $carga_contratada = (float)($row["E"] ?? 0.0000);
                    $porcentaje = ($carga_contratada * .10);
                    $carga_minima = $carga_contratada - $porcentaje;
                    $carga_maxima = $carga_contratada + $porcentaje;

                    $user = new Servicio();
                    $user->rpu = htmlspecialchars(trim($row["A"]));
                    $user->medidor = htmlspecialchars(trim($row["B"]));
                    $user->cuenta = htmlspecialchars(trim($row["C"]));
                    $user->tarifa = htmlspecialchars(trim($row["D"]));
                    $user->carga_contratada = $carga_contratada;
                    $user->carga_conectada = (float)($row["F"] ?? 0.0000);
                    $user->carga_minima = $carga_minima;
                    $user->carga_maxima = $carga_maxima;
                    $user->rmu = htmlspecialchars(trim($row["G"]));
                    $user->direccion = htmlspecialchars(trim($row["H"]));
                    $user->ciudad = htmlspecialchars(trim($row["I"]));
                    $user->colonia = htmlspecialchars(trim($row["J"]));
                    $user->calle_1 = htmlspecialchars(trim($row["K"]));
                    $user->calle_2 = htmlspecialchars(trim($row["L"]));
                    $user->alias = htmlspecialchars(trim($row["M"]));
                    $user->grupo_id = 3;
                    $user->save();

                } catch (QueryException $e) {
                    Log::alert("Error en :: " . $row["A"] . " - " . $e->getMessage());
                    continue;
                } catch (Exception $e) {
                    Log::alert("Error en " . $row["A"] . " - " . $e->getMessage());
                    continue;
                }
            }
        }



    }


}
