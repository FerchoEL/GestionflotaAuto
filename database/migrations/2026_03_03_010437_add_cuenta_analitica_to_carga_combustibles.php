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
            $table->unsignedBigInteger('cuenta_analitica_id')->nullable()->after('vehiculo_id');

            $table->foreign('cuenta_analitica_id')
                ->references('id')
                ->on('cuenta_analiticas')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carga_combustibles', function (Blueprint $table) {
            $table->dropForeign(['cuenta_analitica_id']);
            $table->dropColumn('cuenta_analitica_id');
        });
    }
};
