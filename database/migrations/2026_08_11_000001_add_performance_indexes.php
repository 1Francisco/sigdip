<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices de rendimiento para las consultas más usadas del dashboard,
     * listados e historial (web y app móvil).
     */
    public function up(): void
    {
        // Inspecciones: filtros por estado y por veterinario
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->index('estado', 'inspecciones_estado_index');
            $table->index(['veterinario_id', 'estado'], 'inspecciones_vet_estado_index');
        });

        // Visitas: pendientes ordenadas por fecha, y visitas del médico
        Schema::table('visitas', function (Blueprint $table) {
            $table->index(['estado', 'fecha_programada'], 'visitas_estado_fecha_index');
            $table->index(['veterinario_id', 'estado'], 'visitas_vet_estado_index');
        });
    }

    public function down(): void
    {
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->dropIndex('inspecciones_estado_index');
            $table->dropIndex('inspecciones_vet_estado_index');
        });

        Schema::table('visitas', function (Blueprint $table) {
            $table->dropIndex('visitas_estado_fecha_index');
            $table->dropIndex('visitas_vet_estado_index');
        });
    }
};