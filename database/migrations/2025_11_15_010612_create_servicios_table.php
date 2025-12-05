<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void{

        $Catalogos = config('alumun.table_names.catalogos');

        Schema::create($Catalogos['grupos'], function (Blueprint $table) use ($Catalogos){
            $table->id();
            $table->string('grupo', 150)->default('');
            $table->string('clave', 50)->default('');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create($Catalogos['servicios'], function (Blueprint $table) use ($Catalogos){
            $table->id();
            $table->string('rpu', 30)->default('')->unique();
            $table->string('medidor', 30)->default('')->index();
            $table->string('cuenta', 30)->default('')->index();
            $table->string('tarifa', 6)->default('')->index();
            $table->float('carga_contratada', 12,4)->default(0.0000);
            $table->float('carga_conectada', 12,4)->default(0.00);
            $table->float('carga_minima', 12,4)->default(0.0000);
            $table->float('carga_maxima', 12,4)->default(0.0000);
            $table->string('rmu', 30)->default('')->index();
            $table->string('direccion', 250)->default('');
            $table->string('ciudad', 150)->default('');
            $table->string('colonia', 150)->default('');
            $table->string('calle_1', 150)->default('');
            $table->string('calle_2', 150)->default('');
            $table->string('calle_3', 150)->default('');
            $table->string('alias', 150)->default('');
            $table->boolean('activo')->default(true)->index();
            $table->boolean('es_baja')->default(false)->index();
            $table->date('fecha_baja')->nullable();
            $table->unsignedInteger('grupo_id')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('grupo_id')
                ->references('id')
                ->on($Catalogos['grupos'])
                ->onDelete('set null');

        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void{
        Schema::dropIfExists('servicios');
        Schema::dropIfExists('grupos');
    }
};
