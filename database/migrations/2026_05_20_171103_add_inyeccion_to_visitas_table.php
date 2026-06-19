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
    public function up(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->boolean('inyeccion')->default(false)->after('estado');
        });

        // Actualizar visitas existentes que ya tienen inspección con fecha_inyeccion
        DB::table('visitas')
            ->whereIn('id', function ($query) {
                $query->select('visita_id')
                    ->from('inspecciones')
                    ->whereNotNull('fecha_inyeccion');
            })
            ->update(['inyeccion' => 1]);
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
