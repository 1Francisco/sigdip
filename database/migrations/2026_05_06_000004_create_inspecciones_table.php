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
        Schema::create('inspecciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veterinario_id')->constrained('users');
            $table->foreignId('predio_id')->constrained('predios')->onDelete('cascade');
            $table->string('folio')->unique();
            $table->date('fecha');
            $table->string('tipo_inspeccion')->default('Movilización');
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['borrador', 'sincronizado'])->default('borrador');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspecciones');
    }
};
