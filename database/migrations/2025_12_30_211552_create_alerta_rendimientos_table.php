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
        Schema::create('alerta_rendimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('responsable_user_id');
            $table->unsignedBigInteger('carga_id');
            $table->unsignedBigInteger('motivo_auditoria_id')->nullable();

            $table->foreign('vehiculo_id')->references('id')->on('vehiculos');
            $table->foreign('responsable_user_id')->references('id')->on('users');
            $table->foreign('carga_id')->references('id')->on('carga_combustibles');
            $table->foreign('motivo_auditoria_id')->references('id')->on('motivo_auditorias');

            $table->decimal('rendimiento_detectado', 10, 3);
            $table->decimal('rendimiento_optimo', 10, 3);
            $table->decimal('umbral_aplicado', 10, 3);

            $table->string('estatus')->default('Abierta');

            $table->dateTime('fecha_alerta');
            $table->dateTime('fecha_cierre')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerta_rendimientos');
    }
};
