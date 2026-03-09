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
        Schema::create('alerta_fondeos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('fondeo_id')->nullable();

            $table->string('tipo'); // SOBRE_FONDEO
            $table->text('descripcion')->nullable();

            $table->timestamps();

            $table->foreign('vehiculo_id')
                ->references('id')
                ->on('vehiculos')
                ->cascadeOnDelete();

            $table->foreign('fondeo_id')
                ->references('id')
                ->on('fondeos')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerta_fondeos');
    }
};
