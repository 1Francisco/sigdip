<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->string('dictamen_comite_path')->nullable()->after('observaciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->dropColumn('dictamen_comite_path');
        });
    }
};
