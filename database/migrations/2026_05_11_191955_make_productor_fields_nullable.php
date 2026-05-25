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
            $table->string('rpp')->nullable()->change();
            $table->string('curp', 18)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('productores', function (Blueprint $table) {
            $table->string('rpp')->nullable(false)->change();
            $table->string('curp', 18)->nullable(false)->change();
        });
    }
};
