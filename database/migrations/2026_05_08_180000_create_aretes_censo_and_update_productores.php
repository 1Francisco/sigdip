<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar medico_id a productores
        Schema::table('productores', function (Blueprint $table) {
            $table->unsignedBigInteger('medico_id')->nullable()->after('id');
            $table->string('zona')->nullable()->after('estado'); // Zona A o B
            $table->string('clave_cuarentena')->nullable()->after('zona');
            $table->foreign('medico_id')->references('id')->on('users')->onDelete('set null');
        });

        // Crear tabla de aretes (censo ganadero)
        Schema::create('aretes_censo', function (Blueprint $table) {
            $table->id();
            $table->string('numero_arete')->unique();
            $table->foreignId('productor_id')->nullable()->constrained('productores')->onDelete('cascade');
            $table->foreignId('predio_id')->nullable()->constrained('predios')->onDelete('set null');
            $table->string('raza')->nullable();
            $table->string('sexo')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->integer('edad_meses')->nullable();
            $table->string('sacrificio')->nullable();
            $table->string('archivo_origen')->nullable(); // De qué Excel vino
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aretes_censo');
        Schema::table('productores', function (Blueprint $table) {
            $table->dropForeign(['medico_id']);
            $table->dropColumn(['medico_id', 'zona', 'clave_cuarentena']);
        });
    }
};
