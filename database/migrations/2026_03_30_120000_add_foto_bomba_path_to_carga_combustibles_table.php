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
            $table->string('foto_bomba_path')->nullable()->after('foto_ticket_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carga_combustibles', function (Blueprint $table) {
            $table->dropColumn('foto_bomba_path');
        });
    }
};
