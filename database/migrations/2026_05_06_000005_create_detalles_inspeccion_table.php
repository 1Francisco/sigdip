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
        Schema::create('detalles_inspeccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspeccion_id')->constrained('inspecciones')->onDelete('cascade');
            $table->foreignId('animal_id')->constrained('animales')->onDelete('cascade');
            $table->string('resultado_prueba')->comment('Resultado de la prueba (e.g., Negativo, Reactivo, Pendiente)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_inspeccion');
    }
};
