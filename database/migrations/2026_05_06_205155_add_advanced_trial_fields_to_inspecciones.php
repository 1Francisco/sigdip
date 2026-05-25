<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->date('fecha_prueba_anterior')->nullable();
            $table->string('dictamen_anterior_no')->nullable();
            $table->string('exencion_no')->nullable();
            $table->date('exencion_fecha')->nullable();
            $table->string('hato_libre_no')->nullable();
            $table->date('hato_libre_fecha')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->dropColumn(['fecha_prueba_anterior', 'dictamen_anterior_no', 'exencion_no', 'exencion_fecha', 'hato_libre_no', 'hato_libre_fecha']);
        });
    }
};
