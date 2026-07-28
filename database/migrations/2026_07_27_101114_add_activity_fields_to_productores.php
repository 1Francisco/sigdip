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
        Schema::table('productores', function (Blueprint $table) {
            $table->string('tipo_actividad')->nullable()->after('clave_cuarentena');
            $table->string('sub_tipo_actividad')->nullable()->after('tipo_actividad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productores', function (Blueprint $table) {
            $table->dropColumn(['tipo_actividad', 'sub_tipo_actividad']);
        });
    }
};
