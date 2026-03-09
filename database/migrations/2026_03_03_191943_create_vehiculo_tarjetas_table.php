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
        Schema::create('vehiculo_tarjetas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('tarjeta_combustible_id');

            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->foreign('vehiculo_id')
                ->references('id')
                ->on('vehiculos')
                ->cascadeOnDelete();

            $table->foreign('tarjeta_combustible_id')
                ->references('id')
                ->on('tarjeta_combustibles')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculo_tarjetas');
    }
};
