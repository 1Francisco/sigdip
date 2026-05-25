<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalles_inspeccion', function (Blueprint $table) {
            $table->string('tipo_arete')->after('animal_id')->default('SINIIGA');
        });
    }

    public function down(): void
    {
        Schema::table('detalles_inspeccion', function (Blueprint $table) {
            $table->dropColumn('tipo_arete');
        });
    }
};
