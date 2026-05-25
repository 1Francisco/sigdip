<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->boolean('inyeccion')->default(false)->after('estado');
        });

        // Actualizar visitas existentes que ya tienen inspección con fecha_inyeccion
        DB::statement("
            UPDATE visitas v
            INNER JOIN inspecciones i ON i.visita_id = v.id
            SET v.inyeccion = 1
            WHERE i.fecha_inyeccion IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->dropColumn('inyeccion');
        });
    }
};
