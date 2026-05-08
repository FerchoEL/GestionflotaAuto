<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarjeta_combustibles', function (Blueprint $table) {
            $table->string('empleado_one_card')->nullable()->after('descripcion');
            $table->string('convenio_id_one_card')->nullable()->after('empleado_one_card');
            $table->string('convenio_one_card')->nullable()->after('convenio_id_one_card');
            $table->string('sucursal_one_card')->nullable()->after('convenio_one_card');
            $table->string('nombre_one_card')->nullable()->after('sucursal_one_card');
        });
    }

    public function down(): void
    {
        Schema::table('tarjeta_combustibles', function (Blueprint $table) {
            $table->dropColumn([
                'empleado_one_card',
                'convenio_id_one_card',
                'convenio_one_card',
                'sucursal_one_card',
                'nombre_one_card',
            ]);
        });
    }
};
