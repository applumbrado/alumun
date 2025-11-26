<?php

namespace Database\Seeders;

use App\Classes\FuncionesController;
use App\Models\User;
use App\Models\User\Permission;
use App\Models\User\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void{


        app()['cache']->forget('spatie.permission.cache');

        $F = new FuncionesController();
        $ip   = ""; // $_SERVER['REMOTE_ADDR'];
        $host = ""; // gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $idemp = 1;

        $P1 = Permission::create(['name' => 'all']);
        $P2 = Permission::create(['name' => 'crear']);
        $P3 = Permission::create(['name' => 'guardar']);
        $P4 = Permission::create(['name' => 'editar']);
        $P5 = Permission::create(['name' => 'modificar']);
        $P6 = Permission::create(['name' => 'eliminar']);
        $P7 = Permission::create(['name' => 'consultar']);
        $P8 = Permission::create(['name' => 'imprimir']);
        $P9 = Permission::create(['name' => 'asignar']);
        $P10 = Permission::create(['name' => 'desasignar']);
        $P11 = Permission::create(['name' => 'sysop']);

        $role_admin = Role::create([
            'name' => 'Admin',
            'descripcion' => 'Administrator',
            'abreviatura' => 'ADM',
            'guard_name' => 'web',
        ]);
        $role_admin->permissions()->attach($P1);

        $role_sysop = Role::create([
            'name' => 'SysOp',
            'descripcion' => 'System Operator',
            'abreviatura' => 'SysOp',
            'guard_name' => 'web',
        ]);
        $role_sysop->permissions()->attach($P11);

        $role_invitado = Role::create([
            'name' => 'Invitado',
            'descripcion' => 'Invitado',
            'abreviatura' => 'INV',
            'guard_name' => 'web',
        ]);
        $role_invitado->permissions()->attach($P7);

        $user = new User();
        $user->nombre = 'Administrador';
        $user->username = 'Admin';
        $user->email = 'sentauro@gmail.com';
        $user->password = bcrypt('NxsWry2K');
        $user->genero = 1;
        $user->empresa_id = $idemp;
        $user->ip = $ip;
        $user->host = $host;
        $user->email_verified_at = now();
        $user->save();
        $user->roles()->attach($role_admin);
        $user->permissions()->attach($P1);
        $user->user_address()->create();
        $user->user_data_extend()->create();
        $F->validImage($user,'profile','profile/');

        $user = new User();
        $user->nombre = 'System Operator';
        $user->username = 'SysOp';
        $user->email = 'sysop@example.com';
        $user->password = bcrypt('sysop');
        $user->empresa_id = $idemp;
        $user->ip = $ip;
        $user->host = $host;
        $user->email_verified_at = now();
        $user->save();
        $user->roles()->attach($role_sysop);
        $user->permissions()->attach($P11);
        $user->user_address()->create();
        $user->user_data_extend()->create();
        $F->validImage($user,'profile','profile/');

        $user = new User();
        $user->nombre = 'Invitado';
        $user->username = 'Invitado';
        $user->email = 'invitado@example.com';
        $user->password = bcrypt('Invitado');
        $user->empresa_id = $idemp;
        $user->ip = $ip;
        $user->host = $host;
        $user->email_verified_at = now();
        $user->save();
        $user->roles()->attach($role_invitado);
        $user->permissions()->attach($P7);
        $user->user_address()->create();
        $user->user_data_extend()->create();
        $F->validImage($user,'profile','profile/');

        Role::create(['name'=>'Subdirector de Alumbrado Público y Energía','descripcion'=>'Subdirector de Alumbrado Público y Energía','abreviatura'=>'SCUA','guard_name'=>'web'])->permissions()->attach($P7);
        Role::create(['name'=>'Encargada del Área de pagos de CFE','descripcion'=>'Encargada del Área de pagos de CFE','abreviatura'=>'JOP1','guard_name'=>'web'])->permissions()->attach($P7);
        Role::create(['name'=>'Encargada del Área de Recursos Humanos','descripcion'=>'Encargada del Área de Recursos Humanos','abreviatura'=>'EARH','guard_name'=>'web'])->permissions()->attach($P7);
        Role::create(['name'=>'Encargada del Área Requisiciones','descripcion'=>'Encargada del Área Requisiciones','abreviatura'=>'EAR','guard_name'=>'web'])->permissions()->attach($P7);
        Role::create(['name'=>'Jefe del Departamento de Energía y Tecnología','descripcion'=>'Jefe del Departamento de Energía y Tecnología','abreviatura'=>'JDET','guard_name'=>'web'])->permissions()->attach($P7);
        Role::create(['name'=>'Jefe de Área','descripcion'=>'Jefe de Área','abreviatura'=>'JA','guard_name'=>'web'])->permissions()->attach($P7);

    }


}
