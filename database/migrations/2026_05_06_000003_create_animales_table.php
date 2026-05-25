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
        Schema::create('animales', function (Blueprint $table) {
            $table->id();
            $table->string('numero_arete_siniiga')->unique();
            $table->integer('edad')->comment('Edad en meses');
            $table->string('raza');
            $table->enum('sexo', ['Macho', 'Hembra']);
            $table->foreignId('predio_id')->constrained('predios')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animales');
    }
};
