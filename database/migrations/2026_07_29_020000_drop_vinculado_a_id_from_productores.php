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
                $table->dropColumn('vinculado_a_id');
            });
        } else {
            Schema::table('productores', function (Blueprint $table) {
                $table->dropForeign(['vinculado_a_id']);
                $table->dropColumn('vinculado_a_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('productores', function (Blueprint $table) {
            $table->unsignedBigInteger('vinculado_a_id')->nullable();
        });
    }
};
