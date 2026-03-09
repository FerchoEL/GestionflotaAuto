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
        Schema::create('vehiculo_fondeo_configs', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('vehiculo_id');
    $table->decimal('litros_asignados', 10, 2);

    $table->boolean('activo')->default(true);

    $table->unsignedBigInteger('asignado_por_user_id')->nullable();

    $table->timestamps();

    $table->foreign('vehiculo_id')
        ->references('id')
        ->on('vehiculos')
        ->cascadeOnDelete();

    $table->foreign('asignado_por_user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->index(['vehiculo_id', 'activo']);
});
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_fondeo_configs');
    }
};
