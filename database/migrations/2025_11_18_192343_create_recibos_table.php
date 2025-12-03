<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){

        $Catalogos = config('alumun.table_names.catalogos');

        Schema::create('periodos', function (Blueprint $table) {
           $table->id();
            $table->string('anomes')->unique();
            $table->unsignedInteger('ano')->default(0)->index();
            $table->unsignedInteger('mes')->default(0)->index();
            $table->string('mes_nombre',20)->default('');
            $table->smallInteger('tipo')->default(0);
            $table->smallInteger('digito')->default(0);
            $table->boolean('predeterminado')->default(false)->index();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['anomes', 'ano', 'mes', 'tipo']);

        });

        Schema::create('recibos', function (Blueprint $table)  use ($Catalogos){
            $table->id();
            $table->string('rpu')->nullable();
            $table->string('medidor')->nullable();
            $table->string('cuenta')->nullable();
            $table->string('tarifa')->nullable();
            $table->string('periodo')->nullable();
            $table->text('direccion')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('iva', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->unsignedInteger('periodo_id')->nullable()->index();
            $table->unsignedInteger('servicio_id')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('periodo_id')
                ->references('id')
                ->on($Catalogos['periodos'])
                ->onDelete('set null');

            $table->foreign('servicio_id')
                ->references('id')
                ->on($Catalogos['servicios'])
                ->onDelete('set null');

        });
    }

    public function down(){
        Schema::dropIfExists('recibos');
        Schema::dropIfExists('periodos');
    }
};
