<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsable_auxiliares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('responsable_user_id');
            $table->unsignedBigInteger('auxiliar_user_id');
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('asignado_por_user_id')->nullable();
            $table->timestamps();

            $table->foreign('responsable_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('auxiliar_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('asignado_por_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['responsable_user_id', 'auxiliar_user_id'], 'resp_aux_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsable_auxiliares');
    }
};
