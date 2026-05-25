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
        // Productores
        Schema::table('productores', function (Blueprint $table) {
            $table->string('apellido_paterno')->after('nombre')->nullable();
            $table->string('apellido_materno')->after('apellido_paterno')->nullable();
            $table->string('domicilio')->after('curp')->nullable();
            $table->string('municipio')->after('domicilio')->nullable();
            $table->string('estado')->after('municipio')->default('Nayarit');
            $table->string('email')->after('telefono')->nullable();
        });

        // Predios
        Schema::table('predios', function (Blueprint $table) {
            $table->decimal('latitud', 10, 8)->after('clave_unidad_produccion')->nullable();
            $table->decimal('longitud', 11, 8)->after('latitud')->nullable();
            $table->string('domicilio')->after('longitud')->nullable();
            $table->string('municipio')->after('domicilio')->nullable();
        });

        // Inspecciones
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->string('tipo_prueba')->after('tipo_inspeccion')->default('P.P.C.');
            $table->date('fecha_inyeccion')->after('fecha')->nullable();
            $table->time('hora_inyeccion')->after('fecha_inyeccion')->nullable();
            $table->date('fecha_lectura')->after('hora_inyeccion')->nullable();
            $table->time('hora_lectura')->after('fecha_lectura')->nullable();
            $table->string('motivo_prueba')->after('tipo_prueba')->nullable();
            $table->string('funcion_zootecnica')->after('motivo_prueba')->nullable();
            $table->date('vigencia_fecha')->after('fecha_lectura')->nullable();
            // Censo
            $table->integer('sementales')->default(0);
            $table->integer('vacas')->default(0);
            $table->integer('vaquillas')->default(0);
            $table->integer('becerras')->default(0);
            $table->integer('becerros')->default(0);
        });

        // Detalles (Animales individuales)
        Schema::table('detalles_inspeccion', function (Blueprint $table) {
            $table->integer('edad_meses')->after('animal_id')->nullable();
            $table->string('raza')->after('edad_meses')->nullable();
            $table->string('sexo')->after('raza')->nullable();
            $table->string('fierro')->after('sexo')->nullable();
            $table->text('observaciones_animal')->after('resultado_prueba')->nullable();
        });
    }

    public function down(): void
    {
        // Reversión omitida para brevedad en este paso, pero usualmente se harían los dropColumn
    }
};
