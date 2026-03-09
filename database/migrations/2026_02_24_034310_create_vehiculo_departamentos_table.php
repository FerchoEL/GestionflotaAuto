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
        Schema::create('vehiculo_departamentos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('departamento_id');
            $table->unsignedBigInteger('asignado_por_user_id')->nullable();

            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->cascadeOnDelete();
            $table->foreign('departamento_id')->references('id')->on('departamentos');
            $table->foreign('asignado_por_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculo_departamentos');
    }
};
