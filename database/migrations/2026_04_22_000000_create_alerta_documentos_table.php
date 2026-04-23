<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerta_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_documento_id')->constrained('vehiculo_documentos')->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento')->cascadeOnDelete();
            $table->foreignId('responsable_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo');
            $table->text('descripcion')->nullable();
            $table->string('estatus')->default('Abierta');
            $table->text('comentario')->nullable();
            $table->dateTime('fecha_alerta');
            $table->dateTime('fecha_cierre')->nullable();
            $table->timestamps();

            $table->index(['vehiculo_documento_id', 'tipo', 'estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerta_documentos');
    }
};
