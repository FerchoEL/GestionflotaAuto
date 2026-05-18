<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fondeos', function (Blueprint $table): void {
            $table->foreignId('tarjeta_combustible_id')
                ->nullable()
                ->after('vehiculo_id')
                ->constrained('tarjeta_combustibles')
                ->nullOnDelete();

            $table->index(['vehiculo_id', 'tarjeta_combustible_id'], 'fondeos_vehiculo_tarjeta_idx');
        });

        Schema::table('carga_combustibles', function (Blueprint $table): void {
            $table->foreignId('tarjeta_combustible_id')
                ->nullable()
                ->after('vehiculo_id')
                ->constrained('tarjeta_combustibles')
                ->nullOnDelete();

            $table->index(['vehiculo_id', 'tarjeta_combustible_id'], 'cargas_vehiculo_tarjeta_idx');
        });
    }

    public function down(): void
    {
        Schema::table('carga_combustibles', function (Blueprint $table): void {
            $table->dropIndex('cargas_vehiculo_tarjeta_idx');
            $table->dropConstrainedForeignId('tarjeta_combustible_id');
        });

        Schema::table('fondeos', function (Blueprint $table): void {
            $table->dropIndex('fondeos_vehiculo_tarjeta_idx');
            $table->dropConstrainedForeignId('tarjeta_combustible_id');
        });
    }
};
