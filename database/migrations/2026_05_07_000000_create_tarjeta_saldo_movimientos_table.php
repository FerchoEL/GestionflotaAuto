<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarjeta_saldo_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarjeta_combustible_id')
                ->constrained('tarjeta_combustibles')
                ->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->decimal('monto', 12, 2);
            $table->timestamp('fecha_movimiento');
            $table->foreignId('tarjeta_destino_id')
                ->nullable()
                ->constrained('tarjeta_combustibles')
                ->nullOnDelete();
            $table->foreignId('registrado_por_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('referencia')->nullable();
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->index(['tarjeta_combustible_id', 'fecha_movimiento'], 'tsm_tarjeta_fecha_idx');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarjeta_saldo_movimientos');
    }
};
