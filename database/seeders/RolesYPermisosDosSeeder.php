<?php

namespace Database\Seeders;

use App\Models\User\Permission;
use App\Models\User\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesYPermisosDosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void{

        Permission::create(['name' => 'dashboard','descripcion'=>'Permite ve rel dashboard', 'color' => 'ffab91']);
        Permission::create(['name' => 'crear grupo','descripcion'=>'Permite crear grupos', 'color' => 'ffab91']);
        Permission::create(['name' => 'crear periodo','descripcion'=>'Permite crear periodos', 'color' => 'ffab91']);
        Permission::create(['name' => 'crear servicio','descripcion'=>'Permite crear servicios', 'color' => 'ffab91']);
        Permission::create(['name' => 'procesar recibos','descripcion'=>'Puede procesar recibos', 'color' => 'ffab91']);
        Permission::create(['name' => 'conciliar recibos','descripcion'=>'Permite conciliar recibos', 'color' => 'ffab91']);
        Permission::create(['name' => 'consulta reportes','descripcion'=>'Permite consultar reportes', 'color' => 'ffab91']);

        Permission::create(['name' => 'ver configuraciones','descripcion'=>'Ver el catálogos del configuraciones', 'color' => 'ffab91']);
        Permission::create(['name' => 'ver usuarios','descripcion'=>'Ver el catálogos de usuarios', 'color' => 'ffab91']);
        Permission::create(['name' => 'ver roles','descripcion'=>'Ver el catálogos de roles', 'color' => 'ffab91']);
        Permission::create(['name' => 'ver permisos','descripcion'=>'Ver el catálogos de permisos', 'color' => 'ffab91']);

    }
}
