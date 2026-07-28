<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('productores', function (Blueprint $table) {
                $table->renameColumn('clave_cuarentena', 'clave');
            });
            Schema::table('productores', function (Blueprint $table) {
                $table->dropColumn('clave_productor');
            });
        } else {
            DB::statement('ALTER TABLE productores CHANGE clave_cuarentena clave VARCHAR(255) NULL');
            Schema::table('productores', function (Blueprint $table) {
                $table->dropColumn('clave_productor');
            });
        }
    }

    public function down(): void
    {
        Schema::table('productores', function (Blueprint $table) {
            $table->string('clave_productor')->nullable()->unique()->after('id');
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('productores', function (Blueprint $table) {
                $table->renameColumn('clave', 'clave_cuarentena');
            });
        } else {
            DB::statement('ALTER TABLE productores CHANGE clave clave_cuarentena VARCHAR(255) NULL');
        }
    }
};
