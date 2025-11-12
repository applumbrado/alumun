<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void{

        $tableNamesCatalogos = config('alumun.table_names.catalogos');
        $tableNames = config('alumun.table_names.users');

        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');



        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password')->default('');
            $table->string('nombre')->default('');
            $table->string('ap_paterno')->default('');
            $table->string('ap_materno')->default('');
            $table->string('curp',18)->default('');
            $table->string('emails',500)->default('');
            $table->string('celulares',250)->default('');
            $table->string('telefonos',250)->default('');
            $table->date('fecha_nacimiento')->nullable();
            $table->smallInteger('genero')->default(0);
            $table->string('root',150)->default('');
            $table->string('filename',50)->default('');
            $table->string('filename_png',50)->default('');
            $table->string('filename_thumb',50)->default('');
            $table->unsignedInteger('old_user_id')->default(0)->index();
            $table->unsignedInteger('old_persona_id')->default(0)->index();
            $table->unsignedInteger('old_alumno_id')->default(0)->index();
            $table->string('session_id')->nullable();
            $table->unsignedInteger('imagen_id')->default(0)->index();
            $table->uuid('uuid')->default(DB::raw('uuid_generate_v4()'))->unique();
            $table->unsignedSmallInteger('activo')->default(1);
            $table->unsignedSmallInteger('empresa_id')->default(0);
            $table->string('ip',150)->default('');
            $table->string('host',150)->default('');
            $table->boolean('logged')->default(false);
            $table->timestamp('logged_at')->default(now());
            $table->timestamp('logout_at')->default(now());
            $table->timestamp('email_verified_at')->default(now());
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create($tableNames['user_address'], function (Blueprint $table) use ($tableNames) {
            $table->increments('id');
            $table->string('calle',250)->default('');
            $table->string('num_ext',100)->default('');
            $table->string('num_int',100)->default('');
            $table->string('colonia',150)->default('');
            $table->string('delegacion',150)->default('');
            $table->string('municipio',50)->default('');
            $table->string('estado',50)->default('TABASCO');
            $table->string('pais',25)->default('MÉXICO');
            $table->string('cp',10)->default('');
            $table->unsignedInteger('user_id');
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('user_id')
                ->references('id')
                ->on($tableNames['users'])
                ->onDelete('cascade');
        });


        Schema::create($tableNames['user_extend'], function (Blueprint $table) use ($tableNames) {
            $table->increments('id');
            $table->string('ocupacion',250)->default('')->nullable();
            $table->string('profesion',250)->default('')->nullable();
            $table->string('lugar_trabajo',250)->default('')->nullable();
            $table->string('lugar_nacimiento',250)->default('')->nullable();
            $table->string('nacionalidad',250)->default('')->nullable();
            $table->unsignedInteger('user_id');
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('user_id')
                ->references('id')
                ->on($tableNames['users'])
                ->onDelete('cascade');
        });

        Schema::create($tableNames['categorias'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('categoria',150)->default('')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table) use ($tableNamesCatalogos){
            $table->id();
            $table->string('model_name',128)->default('');
            $table->integer('model_id')->default(0);
            $table->integer('trigger_type')->default(0);
            $table->string('trigger_status',128)->default('');
            $table->string('message',500)->default('');
            $table->string('icon',25)->default('');
            $table->string('status',5)->default('');
            $table->datetime('fecha')->default(now());
            $table->string('ip',250)->default('');
            $table->string('host',250)->default('');
            $table->integer('user_id')->default(0)->index();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on($tableNamesCatalogos['users'])
                ->onDelete('cascade');
        });

        Schema::create($tableNamesCatalogos['imagenes'], function (Blueprint $table) use ($tableNamesCatalogos){
            $table->increments('id');
            $table->dateTime('fecha')->nullable();
            $table->string('root',150)->default("");
            $table->string('image',100)->default("");
            $table->string('image_thumb',100)->default("");
            $table->string('titulo',150)->default("");
            $table->string('descripcion',500)->default("");
            $table->string('momento',10)->default("ANTES");
            $table->unsignedInteger('user__id')->default(0)->index();
            $table->unsignedInteger('denuncia__id')->default(0)->index();
            $table->unsignedInteger('parent__id')->default(0)->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create($tableNamesCatalogos['imagene_parent'], function (Blueprint $table) use ($tableNamesCatalogos){
            $table->increments('id');
            $table->unsignedInteger('imagene_id')->default(0)->index();
            $table->unsignedInteger('imagen_parent_id')->default(0)->index();
            $table->softDeletes();
            $table->timestamps();
            $table->unique([ 'imagene_id','imagen_parent_id']);

            $table->foreign('imagene_id')
                ->references('id')
                ->on($tableNamesCatalogos['imagenes'])
                ->onDelete('cascade');

            $table->foreign('imagen_parent_id')
                ->references('id')
                ->on($tableNamesCatalogos['imagenes'])
                ->onDelete('cascade');

        });

        Schema::create($tableNamesCatalogos['imagene_user'], function (Blueprint $table) use ($tableNamesCatalogos){
            $table->increments('id');
            $table->unsignedInteger('imagene_id')->default(0)->index();
            $table->unsignedInteger('user_id')->default(0)->index();
            $table->softDeletes();
            $table->timestamps();
            $table->unique([ 'imagene_id','user_id']);

            $table->foreign('imagene_id')
                ->references('id')
                ->on($tableNamesCatalogos['imagenes'])
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on($tableNamesCatalogos['users'])
                ->onDelete('cascade');

        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });


        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER DATABASE dbalumun set default_text_search_config = 'spanish'");
        DB::statement("ALTER TABLE users ADD COLUMN searchtext_user TSVECTOR");
        DB::statement("UPDATE users SET searchtext_user = to_tsvector('spanish', coalesce(trim(ap_paterno),'') || ' ' || coalesce(trim(ap_paterno),'') || ' ' || coalesce(trim(nombre),'') || ' ' || coalesce(trim(curp),'') || ' ' || coalesce(trim(username),'') || ' ' || coalesce(trim(email),'') )");
        DB::statement("CREATE INDEX user_searchtext_gin ON users USING GIN(searchtext_user)");
        DB::statement("CREATE TRIGGER ts_searchtext_user1 BEFORE INSERT OR UPDATE ON users FOR EACH ROW EXECUTE PROCEDURE tsvector_update_trigger('searchtext_user', 'pg_catalog.spanish', 'ap_paterno', 'ap_materno', 'nombre', 'curp', 'username', 'email')");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void{

        $tableNamesCatalogos = config('alumun.table_names.catalogos');

        Schema::dropIfExists($tableNamesCatalogos['logs']);
        Schema::dropIfExists($tableNamesCatalogos['imagene_parent']);
        Schema::dropIfExists($tableNamesCatalogos['imagene_user']);
        Schema::dropIfExists($tableNamesCatalogos['imagenes']);

        $tableNames = config('alumun.table_names.users');
        Schema::dropIfExists($tableNames['categorias']);
        Schema::dropIfExists($tableNames['user_address']);
        Schema::dropIfExists($tableNames['user_extend']);
        Schema::dropIfExists($tableNames['users']);

        Schema::dropIfExists('password_resets');

        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');

        Schema::dropIfExists('personal_access_tokens');

    }
};
