<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarjeta_saldo_movimientos', function (Blueprint $table): void {
            $table->string('origen_tipo')->nullable()->after('fecha_movimiento');
            $table->unsignedBigInteger('origen_id')->nullable()->after('origen_tipo');

            $table->index(['origen_tipo', 'origen_id'], 'tsm_origen_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tarjeta_saldo_movimientos', function (Blueprint $table): void {
            $table->dropIndex('tsm_origen_idx');
            $table->dropColumn(['origen_tipo', 'origen_id']);
        });
    }
};
