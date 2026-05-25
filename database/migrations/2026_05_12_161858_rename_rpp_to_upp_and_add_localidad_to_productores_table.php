<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Usar DB::statement para MariaDB < 10.5.2
        DB::statement('ALTER TABLE productores CHANGE rpp upp VARCHAR(255) NULL');
        
        Schema::table('productores', function (Blueprint $table) {
            $table->string('localidad')->nullable()->after('municipio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE productores CHANGE upp rpp VARCHAR(255) NULL');

        Schema::table('productores', function (Blueprint $table) {
            $table->dropColumn('localidad');
        });
    }
};
