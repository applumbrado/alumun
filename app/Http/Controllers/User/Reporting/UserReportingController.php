<?php

namespace App\Http\Controllers\User\Reporting;

use App\Classes\FuncionesController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserReportingController extends Controller{

    protected $arSelect = [
        'id', 'username', 'email', 'nombre','ap_paterno','ap_materno', 'curp','emails','celulares','telefonos',
        'fecha_nacimiento','genero',
        'user_address.calle', 'user_address.num_ext', 'user_address.num_int', 'user_address.colonia',
        'user_address.localidad', 'user_address.municipio', 'user_address.estado', 'user_address.pais', 'user_address.cp',
        'user_data_extend.lugar_nacimiento','user_data_extend.ocupacion','user_data_extend.profesion','user_data_extend.lugar_trabajo','user_data_extend.nacionalidad',
        'user_alumno.matricula_interna','user_alumno.matricula_oficial','user_alumno.email_alumno',
        'user_alumno.enfermedades','user_alumno.reacciones_alergicas','user_alumno.tipo_sangre',
        'user_alumno.beca_sep','user_alumno.beca_arji','user_alumno.beca_sp','user_alumno.beca_bach',
        'user_alumno.es_baja','user_alumno.motivo_baja',
        'user_alumno.fecha_ingreso','user_alumno.observaciones',
        'grupos.clave_grupo', 'grupos.grupo',
    ];

    public function downloadUsersData(){
        FuncionesController::setConfigOne();

        $filename = 'data_user_' . date('Ymd_His') . '.csv';
        $headers = $this->arSelect;

        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');

            // Encabezados limpios
            $cleanHeaders = array_map(function($header) {
                $header = str_replace(array('user_address.', 'user_data_extend.', 'user_alumno.', 'grupos.'), '', $header);
                return $header;
            }, $headers);

            fputcsv($file, $cleanHeaders);

            $data = User::with([
                'user_address' => function($query) {
                    return $query->select('calle', 'num_ext', 'num_int', 'colonia', 'localidad', 'municipio', 'estado', 'pais', 'cp', 'user_id');
                },
                'user_data_extend' => function($query) {
                    return $query->select('lugar_nacimiento','ocupacion','profesion','lugar_trabajo','nacionalidad');
                },
                'user_alumno' => function($query) {
                    return $query->select('matricula_interna','matricula_oficial','email_alumno',
                        'enfermedades','reacciones_alergicas','tipo_sangre',
                        'beca_sep','beca_arji','beca_sp','beca_bach',
                        'es_baja','motivo_baja',
                        'fecha_ingreso','observaciones');
                },
                'grupos' => function($query) {
                    return $query->select('clave_grupo', 'grupo');
                }
            ])
            ->cursor();

            foreach ($data as $user) {
                $csvRow = [];
                foreach ($headers as $header) {
                    // Si es un campo de relación, verificar que la relación exista
                    if (str_contains($header, 'user_address.')) {
                        $field = str_replace('user_address.', '', $header);
                        $csvRow[] = $user->user_address ? ($user->user_address->$field ?? '') : '';
                    } else if (str_contains($header, 'user_data_extend.')) {
                        $field = str_replace('user_data_extend.', '', $header);
                        $csvRow[] = $user->user_data_extend ? ($user->user_data_extend->$field ?? '') : '';
                    } else if (str_contains($header, 'user_alumno.')) {
                        $field = str_replace('user_alumno.', '', $header);
                        $csvRow[] = $user->user_alumno ? ($user->user_alumno->$field ?? '') : '';
                    } else if (str_contains($header, 'grupos.')) {
                        $field = str_replace('grupos.', '', $header);
                        // Para relaciones many-to-many, tomamos el primer grupo o concatenamos varios
                        if ($user->grupos->isNotEmpty()) {
                            if ($field === 'clave_grupo') {
                                $csvRow[] = $user->grupos->pluck('clave_grupo')->unique()->implode(', ');
                            } else if ($field === 'grupo') {
                                $csvRow[] = $user->grupos->pluck('grupo')->unique()->implode(', ');
                            } else {
                                $csvRow[] = '';
                            }
                        } else {
                            $csvRow[] = '';
                        }
                    } else {
                        $csvRow[] = $user->$header ?? '';
                    }

                }
                fputcsv($file, $csvRow);
            }

            fclose($file);
        };

        return streamCsvResponse($callback, $filename);
    }
}
