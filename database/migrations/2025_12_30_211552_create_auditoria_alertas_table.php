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
        Schema::create('auditoria_alertas', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('alerta_id');
            $table->unsignedBigInteger('usuario_id');

            $table->foreign('alerta_id')->references('id')->on('alerta_rendimientos')->cascadeOnDelete();
            $table->foreign('usuario_id')->references('id')->on('users');

            $table->text('comentario');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_alertas');
    }
};
