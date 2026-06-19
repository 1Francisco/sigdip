<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection($this->getConnection())->getDriverName() === 'sqlite') {
            Schema::table('productores', function (Blueprint $table) {
                $table->renameColumn('rpp', 'upp');
            });
        } else {
            // Usar DB::statement para MariaDB < 10.5.2
            DB::statement('ALTER TABLE productores CHANGE rpp upp VARCHAR(255) NULL');
        }

        Schema::table('productores', function (Blueprint $table) {
            $table->string('localidad')->nullable()->after('municipio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection($this->getConnection())->getDriverName() === 'sqlite') {
            Schema::table('productores', function (Blueprint $table) {
                $table->renameColumn('upp', 'rpp');
            });
        } else {
            DB::statement('ALTER TABLE productores CHANGE upp rpp VARCHAR(255) NULL');
        }

        Schema::table('productores', function (Blueprint $table) {
            $table->dropColumn('localidad');
        });
    }
};
