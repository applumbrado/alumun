<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void{

        $Catalogos = config('alumun.table_names.catalogos');

        Schema::create('archivosplanos', function (Blueprint $table) use ($Catalogos) {
            $table->id();

            $table->unsignedBigInteger('periodo_id')->nullable()->index();
            $table->unsignedBigInteger('grupo_id')->nullable()->index();
            $table->unsignedInteger('consecutivo');

            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('disk', 30)->default('public');
            $table->string('path', 500);

            $table->unsignedBigInteger('size')->default(0);
            $table->string('mime', 120)->nullable();

            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['periodo_id', 'grupo_id', 'consecutivo']);


            $table->foreign('periodo_id')
                ->references('id')
                ->on($Catalogos['periodos'])
                ->onDelete('cascade');

            $table->foreign('grupo_id')
                ->references('id')
                ->on($Catalogos['grupos'])
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on($Catalogos['users'])
                ->onDelete('cascade');



        });

    }

    public function down(): void{
        Schema::dropIfExists('archivosplanos');
    }
};
