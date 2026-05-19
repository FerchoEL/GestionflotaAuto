<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizarAsignacionesCerradas('vehiculo_departamentos');
        $this->normalizarAsignacionesCerradas('vehiculo_localidads');
        $this->normalizarAsignacionesCerradas('vehiculo_responsables');
        $this->normalizarAsignacionesCerradas('vehiculo_tarjetas');

        $this->resolverDuplicadosActivosPorVehiculo('vehiculo_departamentos');
        $this->resolverDuplicadosActivosPorVehiculo('vehiculo_localidads');
        $this->resolverDuplicadosActivosPorVehiculo('vehiculo_responsables');
        $this->resolverDuplicadosActivosPorVehiculo('vehiculo_tarjetas');
        $this->resolverDuplicadosActivosPorTarjeta();

        $this->agregarConstraintActivoUnicoPorVehiculo('vehiculo_departamentos', 'vd_activo_unico_vehiculo');
        $this->agregarConstraintActivoUnicoPorVehiculo('vehiculo_localidads', 'vl_activo_unico_vehiculo');
        $this->agregarConstraintActivoUnicoPorVehiculo('vehiculo_responsables', 'vr_activo_unico_vehiculo');
        $this->agregarConstraintActivoUnicoPorVehiculo('vehiculo_tarjetas', 'vt_activo_unico_vehiculo');
        $this->agregarConstraintActivoUnicoPorTarjeta();
    }

    public function down(): void
    {
        $this->eliminarConstraintActivoUnicoPorTarjeta();
        $this->eliminarConstraintActivoUnicoPorVehiculo('vehiculo_tarjetas', 'vt_activo_unico_vehiculo');
        $this->eliminarConstraintActivoUnicoPorVehiculo('vehiculo_responsables', 'vr_activo_unico_vehiculo');
        $this->eliminarConstraintActivoUnicoPorVehiculo('vehiculo_localidads', 'vl_activo_unico_vehiculo');
        $this->eliminarConstraintActivoUnicoPorVehiculo('vehiculo_departamentos', 'vd_activo_unico_vehiculo');
    }

    private function normalizarAsignacionesCerradas(string $table): void
    {
        DB::table($table)
            ->where('activo', true)
            ->whereNotNull('fecha_fin')
            ->update([
                'activo' => false,
            ]);
    }

    private function resolverDuplicadosActivosPorVehiculo(string $table): void
    {
        $vehiculoIds = DB::table($table)
            ->select('vehiculo_id')
            ->where('activo', true)
            ->groupBy('vehiculo_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('vehiculo_id');

        foreach ($vehiculoIds as $vehiculoId) {
            $activos = DB::table($table)
                ->where('vehiculo_id', $vehiculoId)
                ->where('activo', true)
                ->orderByDesc('fecha_inicio')
                ->orderByDesc('id')
                ->get();

            $idsAConservar = $activos->take(1)->pluck('id');
            $idsACerrar = $activos->skip(1)->pluck('id');

            if ($idsACerrar->isEmpty()) {
                continue;
            }

            DB::table($table)
                ->whereIn('id', $idsACerrar)
                ->update([
                    'activo' => false,
                    'fecha_fin' => DB::raw('COALESCE(fecha_fin, fecha_inicio)'),
                    'updated_at' => now(),
                ]);
        }
    }

    private function resolverDuplicadosActivosPorTarjeta(): void
    {
        $tarjetaIds = DB::table('vehiculo_tarjetas')
            ->select('tarjeta_combustible_id')
            ->where('activo', true)
            ->groupBy('tarjeta_combustible_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('tarjeta_combustible_id');

        foreach ($tarjetaIds as $tarjetaId) {
            $activos = DB::table('vehiculo_tarjetas')
                ->where('tarjeta_combustible_id', $tarjetaId)
                ->where('activo', true)
                ->orderByDesc('fecha_inicio')
                ->orderByDesc('id')
                ->get();

            $idsACerrar = $activos->skip(1)->pluck('id');

            if ($idsACerrar->isEmpty()) {
                continue;
            }

            DB::table('vehiculo_tarjetas')
                ->whereIn('id', $idsACerrar)
                ->update([
                    'activo' => false,
                    'fecha_fin' => DB::raw('COALESCE(fecha_fin, fecha_inicio)'),
                    'updated_at' => now(),
                ]);
        }
    }

    private function agregarConstraintActivoUnicoPorVehiculo(string $table, string $indexName): void
    {
        $column = 'activo_unico_vehiculo_id';

        if (! Schema::hasColumn($table, $column)) {
            Schema::table($table, function (Blueprint $table) use ($column, $indexName): void {
                $generated = 'CASE WHEN activo = 1 THEN vehiculo_id ELSE NULL END';
                // MySQL rejects STORED generated columns whose base FK column uses
                // ON DELETE CASCADE. A virtual generated column still lets us keep
                // the nullable unique guard across engines.
                $definition = $table->unsignedBigInteger($column)->nullable()->virtualAs($generated);

                $definition->unique($indexName);
            });
        }
    }

    private function eliminarConstraintActivoUnicoPorVehiculo(string $table, string $indexName): void
    {
        $column = 'activo_unico_vehiculo_id';

        if (Schema::hasColumn($table, $column)) {
            Schema::table($table, function (Blueprint $table) use ($column, $indexName): void {
                $table->dropUnique($indexName);
                $table->dropColumn($column);
            });
        }
    }

    private function agregarConstraintActivoUnicoPorTarjeta(): void
    {
        $column = 'activo_unico_tarjeta_id';

        if (! Schema::hasColumn('vehiculo_tarjetas', $column)) {
            Schema::table('vehiculo_tarjetas', function (Blueprint $table) use ($column): void {
                $generated = 'CASE WHEN activo = 1 THEN tarjeta_combustible_id ELSE NULL END';
                // See note above: prefer a virtual generated column for cross-engine
                // compatibility when the base column participates in foreign keys.
                $definition = $table->unsignedBigInteger($column)->nullable()->virtualAs($generated);

                $definition->unique('vt_activo_unico_tarjeta');
            });
        }
    }

    private function eliminarConstraintActivoUnicoPorTarjeta(): void
    {
        $column = 'activo_unico_tarjeta_id';

        if (Schema::hasColumn('vehiculo_tarjetas', $column)) {
            Schema::table('vehiculo_tarjetas', function (Blueprint $table) use ($column): void {
                $table->dropUnique('vt_activo_unico_tarjeta');
                $table->dropColumn($column);
            });
        }
    }
};
