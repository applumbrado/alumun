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
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        DB::statement('CREATE TEXT SEARCH CONFIGURATION es (COPY = spanish)');
        DB::statement('
        ALTER TEXT SEARCH CONFIGURATION es
        ALTER MAPPING FOR hword, word
        WITH unaccent, spanish_stem');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void{
        DB::statement('DROP TEXT SEARCH CONFIGURATION IF EXISTS es CASCADE');
        DB::statement('DROP EXTENSION IF EXISTS unaccent CASCADE');
    }
};
