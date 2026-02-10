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
        Schema::create('carga_combustibles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehiculo_id');
            
            $table->unsignedBigInteger('user_id'); // operador (chofer o responsable)

            $table->foreign('vehiculo_id')->references('id')->on('vehiculos');
            $table->foreign('user_id')->references('id')->on('users');
            $table->dateTime('fecha_carga');
            $table->unsignedInteger('km_odometro');
            $table->decimal('litros', 10, 2);
            $table->decimal('importe', 12, 2)->nullable();

            $table->string('foto_odometro_path');
            $table->string('foto_ticket_path');
             // Índice para consultas por vehículo y fecha
            $table->index(['vehiculo_id', 'fecha_carga']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carga_combustibles');
    }
};
