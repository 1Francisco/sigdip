<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalles_inspeccion', function (Blueprint $table) {
            $table->string('motivo_no_aplica', 255)->nullable()->after('resultado_prueba');
        });
    }

    public function down(): void
    {
        Schema::table('detalles_inspeccion', function (Blueprint $table) {
            $table->dropColumn('motivo_no_aplica');
        });
    }
};
