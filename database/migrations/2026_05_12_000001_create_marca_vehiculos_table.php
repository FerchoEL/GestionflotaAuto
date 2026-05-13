<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marca_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('vehiculos', function (Blueprint $table) {
            $table->foreignId('marca_vehiculo_id')
                ->nullable()
                ->after('vin')
                ->constrained('marca_vehiculos');
        });

        $timestamp = now();

        $marcas = DB::table('vehiculos')
            ->whereNotNull('marca')
            ->pluck('marca')
            ->map(fn ($marca) => trim((string) $marca))
            ->filter()
            ->unique()
            ->values();

        foreach ($marcas as $marca) {
            $exists = DB::table('marca_vehiculos')
                ->where('nombre', $marca)
                ->exists();

            if (! $exists) {
                DB::table('marca_vehiculos')->insert([
                    'nombre' => $marca,
                    'activo' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }

        $marcaIds = DB::table('marca_vehiculos')->pluck('id', 'nombre');

        DB::table('vehiculos')
            ->select('id', 'marca')
            ->whereNotNull('marca')
            ->orderBy('id')
            ->chunkById(100, function ($vehiculos) use ($marcaIds, $timestamp) {
                foreach ($vehiculos as $vehiculo) {
                    $marca = trim((string) $vehiculo->marca);

                    if ($marca === '') {
                        continue;
                    }

                    DB::table('vehiculos')
                        ->where('id', $vehiculo->id)
                        ->update([
                            'marca' => $marca,
                            'marca_vehiculo_id' => $marcaIds[$marca] ?? null,
                            'updated_at' => $timestamp,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marca_vehiculo_id');
        });

        Schema::dropIfExists('marca_vehiculos');
    }
};
