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
            $table->unsignedBigInteger('registrada_por_user_id')
                ->nullable()
                ->after('motivo_correccion');

            $table->dateTime('fecha_registro_real')
                ->nullable()
                ->after('registrada_por_user_id');

            $table->foreign('registrada_por_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carga_combustibles', function (Blueprint $table) {
            $table->dropForeign(['registrada_por_user_id']);
            $table->dropColumn([
                'registrada_por_user_id',
                'fecha_registro_real',
            ]);
        });
    }
};
