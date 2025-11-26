<?php

namespace Database\Seeders;

use App\Classes\FuncionesController;
use App\Models\User;
use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportUsersExternalSeeder extends Seeder{


    /**
     * Run the database seeds.
     */
    public function run(): void{

        $F = new FuncionesController();
        $ip   = ""; // $F->get_client_ip();
        $host = ""; // gethostbyaddr($ip);
        $idemp = 1;

        $path = '/home/vagrant/alumun/otros/users.xlsx';
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        foreach ($rows as $index => $row) {
            try{

                if ($index === 1) continue;

                $user = new User();
                $user->nombre = $row["B"];
                $user->ap_paterno = $row["C"];
                $user->ap_materno = $row["D"];
                $user->username = $row["E"];
                $user->email = strtolower(trim($row["I"]));
                $user->password = bcrypt($row["E"]);
                $user->curp = $row["E"];
                $user->celulares = $row["H"];
                $user->genero = 1;
                $user->empresa_id = $idemp;
                $user->ip = $ip;
                $user->host = $host;
                $user->email_verified_at = now();
                $user->save();
                $user->roles()->attach(3);
                $user->permissions()->attach(7);
                $user->user_address()->create();
                $user->user_data_extend()->create(['ocupacion'=>$row["H"]]);
                $F->validImage($user,'profile','profile/');
                $user->save();

            }catch (QueryException $e){
                Log::alert("Error en :: ".$row["A"]." - ".$e->getMessage());
                continue;
            }catch (Exception $e){
                Log::alert("Error en ".$row["A"]." - ".$e->getMessage());
                continue;
            }
        }



    }



}
