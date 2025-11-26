<?php

namespace Database\Seeders;

use App\Models\Catalogos\Grupo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateGruposSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void{
        Grupo::create(['grupo' => 'M0HO', 'clave' => 'M0HO']);
        Grupo::create(['grupo' => 'M0HP', 'clave' => 'M0HP']);
        Grupo::create(['grupo' => 'M0HQ', 'clave' => 'M0HQ']);
    }
}
