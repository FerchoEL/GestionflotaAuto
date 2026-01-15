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
        Schema::create('fondeos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('fondeado_por_user_id')->nullable();

            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->cascadeOnDelete();
            $table->foreign('fondeado_por_user_id')->references('id')->on('users');

            
            $table->date('semana_inicio');
            $table->date('semana_fin');
            $table->decimal('litros_consumidos', 10, 2);
            $table->decimal('litros_a_fondear', 10, 2);
            $table->string('estatus')->default('Pendiente');
            
            $table->dateTime('fecha_fondeado')->nullable();
            $table->string('comentario')->nullable();
            $table->timestamps();

            $table->unique(['vehiculo_id','semana_inicio','semana_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fondeos');
    }
};
