<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predios', function (Blueprint $table) {
            $table->dropUnique('predios_clave_unidad_produccion_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('predios', function (Blueprint $table) {
            $table->unique('clave_unidad_produccion');
        });
    }
};
