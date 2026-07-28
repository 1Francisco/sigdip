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
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->string('grupo_id')->nullable()->index()->after('clave_interna');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->dropColumn('grupo_id');
        });
    }
};
