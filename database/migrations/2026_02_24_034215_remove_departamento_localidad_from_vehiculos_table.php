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
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropForeign(['localidad_id']);

            $table->dropColumn(['departamento_id', 'localidad_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->unsignedBigInteger('departamento_id')->nullable();
            $table->unsignedBigInteger('localidad_id')->nullable();
        });
    }
};
