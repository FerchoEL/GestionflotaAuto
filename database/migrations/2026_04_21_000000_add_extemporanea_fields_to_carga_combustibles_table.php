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
        Schema::table('carga_combustibles', function (Blueprint $table) {
            $table->boolean('es_extemporanea')
                ->default(false)
                ->after('cuenta_analitica_id');

            $table->text('motivo_correccion')
                ->nullable()
                ->after('es_extemporanea');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carga_combustibles', function (Blueprint $table) {
            $table->dropColumn([
                'es_extemporanea',
                'motivo_correccion',
            ]);
        });
    }
};
