<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){

        $Catalogos = config('alumun.table_names.catalogos');

        Schema::create('periodos', function (Blueprint $table) {
           $table->id();
            $table->string('periodo')->default('')->index();
            $table->unsignedInteger('ano')->default(0)->index();
            $table->unsignedInteger('mes')->default(0)->index();
            $table->string('mes_nombre',20)->default('');
            $table->smallInteger('tipo')->default(0);
            $table->smallInteger('digito')->default(0);
            $table->boolean('predeterminado')->default(false)->index();
            $table->boolean('activo')->default(true)->index();
            $table->boolean('bloqueado')->default(false)->index();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['ano', 'mes', 'tipo']);

        });

        Schema::create('recibos', function (Blueprint $table)  use ($Catalogos){
            $table->id();
            $table->string('rpu')->default('')->index();
            $table->string('periodo')->default('')->index()->comment('Se obtiene de la tabla Periodos');
            $table->string('medidor')->default('');
            $table->string('cuenta')->default('');
            $table->string('tarifa')->default('');
            $table->text('direccion')->default('');
            $table->date('desde')->nullable()->default(null);
            $table->date('hasta')->nullable()->default(null);

            $table->decimal('consumo', 12, 2)->default(0);
            $table->decimal('demanda', 12, 2)->default(0);
            $table->decimal('reactivos', 12, 2)->default(0);
            $table->decimal('factor_potencia', 12, 2)->default(0);
            $table->decimal('factor_carga', 12, 2)->default(0);

            $table->decimal('energia', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('dap', 12, 2)->default(0);
            $table->decimal('cargos_y_depositos', 12, 2)->default(0);
            $table->decimal('creditos_y_redondeos', 12, 2)->default(0);
            $table->decimal('validacion_total', 10, 2)->default(0);
            $table->decimal('diferencia', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('total_recibo', 10, 2)->default(0);
            $table->unsignedInteger('periodo_id')->nullable()->index();
            $table->unsignedInteger('servicio_id')->nullable()->index();
            $table->string('xml_file')->default('');
            $table->string('pdf_file')->default('');
            $table->boolean('rpu_ok')->default(false)->index();
            $table->boolean('periodo_ok')->default(false)->index();
            $table->boolean('total_ok')->default(false)->index();
            $table->boolean('consumo_ok')->default(false)->index();
            $table->boolean('desde_ok')->default(false)->index();
            $table->boolean('hasta_ok')->default(false)->index();
            $table->boolean('activo')->default(true)->index();
            $table->boolean('bloqueado')->default(false)->index();
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

        Schema::create('expedientes', function (Blueprint $table)  use ($Catalogos){
            $table->id();
            $table->string('archivo_de_cuadre_1')->default('');
            $table->string('archivo_de_cuadre_2')->default('');
            $table->string('archivo_de_cuadre_3')->default('');
            $table->string('archivo_de_factura_1')->default('');
            $table->string('archivo_de_factura_2')->default('');
            $table->string('archivo_de_factura_3')->default('');
            $table->string('ruta_recibos')->default('');
            $table->unsignedInteger('periodo_id')->nullable()->index();
            $table->unsignedInteger('recibo_id')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('periodo_id')
                ->references('id')
                ->on($Catalogos['periodos'])
                ->onDelete('set null');

            $table->foreign('recibo_id')
                ->references('id')
                ->on($Catalogos['recibos'])
                ->onDelete('set null');

        });









        }

    public function down(){
        Schema::dropIfExists('expedientes');
        Schema::dropIfExists('recibos');
        Schema::dropIfExists('periodos');
    }
};
