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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->unsignedBigInteger('tipo_vehiculo_id');
            $table->unsignedBigInteger('departamento_id')->nullable();
            $table->unsignedBigInteger('localidad_id')->nullable();
            $table->unsignedBigInteger('estatus_id');
            $table->unsignedBigInteger('centro_costo_id')->nullable();
            
            $table->enum('tipo_combustible', ['gasolina', 'diesel'])->nullable();
            $table->enum('transmision', ['manual', 'automatica'])->nullable();

            $table->string('centro_costo')->nullable();
            $table->string('placas')->unique();
            $table->string('vin')->nullable()->unique();

            $table->string('marca');
            $table->string('modelo');
            $table->unsignedSmallInteger('anio')->nullable();
            $table->string('color')->nullable();

            $table->decimal('capacidad_tanque_litros', 10, 2)->nullable();
            $table->decimal('rendimiento_optimo_km_l', 10, 2);
            $table->decimal('tolerancia_pct', 5, 2)->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Foreign keys EXPLÍCITAS
            $table->foreign('tipo_vehiculo_id')->references('id')->on('tipo_vehiculos');
            $table->foreign('departamento_id')->references('id')->on('departamentos');
            $table->foreign('localidad_id')->references('id')->on('localidades');
            $table->foreign('estatus_id')->references('id')->on('vehiculo_estatus');
            $table->foreign('centro_costo_id')->references('id')->on('centros_costo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
