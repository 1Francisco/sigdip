<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalles_inspeccion', function (Blueprint $table) {
            $table->boolean('agregado_en_lectura')->default(false)->after('observaciones_animal');
        });
    }

    public function down(): void
    {
        Schema::table('detalles_inspeccion', function (Blueprint $table) {
            $table->dropColumn('agregado_en_lectura');
        });
    }
};
