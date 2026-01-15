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
        Schema::create('rendimientos', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('carga_id');
            $table->unsignedBigInteger('vehiculo_id');

            $table->foreign('carga_id')->references('id')->on('carga_combustibles');
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos');

            $table->unsignedInteger('km_anterior')->nullable();
            $table->unsignedInteger('km_recorridos')->nullable();
            $table->decimal('rendimiento_km_l', 10, 3)->nullable();

            $table->boolean('es_base')->default(false);
            $table->boolean('evaluado')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rendimientos');
    }
};
