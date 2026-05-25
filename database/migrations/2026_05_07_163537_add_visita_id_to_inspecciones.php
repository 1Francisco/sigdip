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
            $table->foreignId('visita_id')->nullable()->constrained('visitas')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->dropForeign(['visita_id']);
            $table->dropColumn('visita_id');
        });
    }
};
