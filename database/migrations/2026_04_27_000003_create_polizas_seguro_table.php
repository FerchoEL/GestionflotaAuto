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
        Schema::create('polizas_seguro', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehiculo_documento_id')->unique();
            $table->unsignedBigInteger('aseguradora_id');
            $table->decimal('costo_poliza', 10, 2);
            $table->unsignedBigInteger('tipo_pago_id');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->foreign('vehiculo_documento_id')
                ->references('id')
                ->on('vehiculo_documentos')
                ->onDelete('cascade');

            $table->foreign('aseguradora_id')
                ->references('id')
                ->on('aseguradoras');

            $table->foreign('tipo_pago_id')
                ->references('id')
                ->on('tipo_pagos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polizas_seguro');
    }
};
