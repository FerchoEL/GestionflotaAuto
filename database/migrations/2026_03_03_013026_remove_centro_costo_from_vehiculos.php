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

            // Eliminar FK primero
            if (Schema::hasColumn('vehiculos', 'centro_costo_id')) {
                $table->dropForeign(['centro_costo_id']);
                $table->dropColumn('centro_costo_id');
            }

            // Eliminar campo string
            if (Schema::hasColumn('vehiculos', 'centro_costo')) {
                $table->dropColumn('centro_costo');
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {

            $table->unsignedBigInteger('centro_costo_id')->nullable();
            $table->string('centro_costo')->nullable();

            $table->foreign('centro_costo_id')
                ->references('id')
                ->on('cuenta_analiticas') // ya renombrada
                ->nullOnDelete();

        });
    }
};
