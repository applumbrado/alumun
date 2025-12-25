<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up():void {

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
            $table->unique(['ano', 'mes']);

        });

        Schema::create('recibos', function (Blueprint $table)  use ($Catalogos){
            $table->id();
            $table->string('rpu')->default('')->index();
            $table->string('periodo')->default('')->index()->comment('Se obtiene de la tabla Periodos');
            $table->string('periodo_extend')->default('');
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
            $table->boolean('validado')->default(false)->index();
            $table->boolean('activo')->default(true)->index();
            $table->boolean('bloqueado')->default(false)->index();
            $table->timestamp('conciliado_at')->nullable()->index();

            $table->softDeletes();
            $table->timestamps();
            $table->unique(['rpu','periodo','periodo_id']);

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
            $table->unsignedInteger('recibo_id')->nullable()->index();
            $table->unsignedInteger('periodo_id')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['recibo_id','periodo_id']);

            $table->foreign('recibo_id')
                ->references('id')
                ->on($Catalogos['recibos'])
                ->onDelete('set null');


            $table->foreign('periodo_id')
                ->references('id')
                ->on($Catalogos['periodos'])
                ->onDelete('set null');
        });

        Schema::create('conceptos', function (Blueprint $table)  use ($Catalogos){
            $table->id();
            $table->string('concepto1')->default('');
            $table->float('importe1',10,2)->default(0);
            $table->string('concepto2')->default('');
            $table->float('importe2',10,2)->default(0);
            $table->string('concepto3')->default('');
            $table->float('importe3',10,2)->default(0);
            $table->string('concepto4')->default('');
            $table->float('importe4',10,2)->default(0);
            $table->string('concepto5')->default('');
            $table->float('importe5',10,2)->default(0);
            $table->string('concepto6')->default('');
            $table->float('importe6',10,2)->default(0);
            $table->string('concepto7')->default('');
            $table->float('importe7',10,2)->default(0);
            $table->string('concepto8')->default('');
            $table->float('importe8',10,2)->default(0);
            $table->string('concepto9')->default('');
            $table->float('importe9',10,2)->default(0);
            $table->string('concepto10')->default('');
            $table->float('importe10',10,2)->default(0);
            $table->unsignedInteger('recibo_id')->nullable()->index();
            $table->unsignedInteger('periodo_id')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['recibo_id','periodo_id']);

            $table->foreign('recibo_id')
                ->references('id')
                ->on($Catalogos['recibos'])
                ->onDelete('set null');

            $table->foreign('periodo_id')
                ->references('id')
                ->on($Catalogos['periodos'])
                ->onDelete('set null');
        });












    }

    public function down():void {
        Schema::dropIfExists('expedientes');
        Schema::dropIfExists('recibos');
        Schema::dropIfExists('periodos');
    }

};
