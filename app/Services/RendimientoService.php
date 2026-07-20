<?php

namespace App\Services;

use App\Models\CargaCombustible;
use App\Models\Rendimiento;
use App\Models\AlertaRendimiento;
use App\Models\ParametroSistema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RendimientoService
{
    private const UMBRAL_AUMENTO_ANORMAL_PCT = 40;

    /**
     * Procesa una carga de combustible y calcula el rendimiento.
     */
    public function procesarCarga(CargaCombustible $carga): void
    {
        DB::transaction(fn () => $this->procesarCargaInterna($carga));
    }

    public function recalcularDesdeCarga(CargaCombustible $carga): void
    {
        DB::transaction(function () use ($carga) {
            $cargas = $this->obtenerCargasDesde($carga);

            if ($cargas->isEmpty()) {
                return;
            }

            $cargaIds = $cargas->pluck('id');

            AlertaRendimiento::whereIn('carga_id', $cargaIds)->delete();
            Rendimiento::whereIn('carga_id', $cargaIds)->delete();

            foreach ($cargas as $cargaARecalcular) {
                $this->procesarCargaInterna($cargaARecalcular);
            }
        });
    }

    protected function procesarCargaInterna(CargaCombustible $carga): void
    {
        // Evitar reprocesos: si ya existe rendimiento para esta carga, no duplicar
        if (Rendimiento::where('carga_id', $carga->id)->exists()) {
            return;
        }

        $cargaAnterior = $this->obtenerCargaAnterior($carga);

        // Si no hay carga anterior, se marca como base
        if (! $cargaAnterior) {
            Rendimiento::create([
                'carga_id' => $carga->id,
                'vehiculo_id' => $carga->vehiculo_id,
                'km_anterior' => null,
                'km_recorridos' => 0,
                'rendimiento_km_l' => null,
                'es_base' => true,
                'evaluado' => false,
            ]);

            return;
        }

        // Calcular kilómetros recorridos
        $kmRecorridos = $carga->km_odometro - $cargaAnterior->km_odometro;

        $litrosEvaluados = (float) $carga->litros;

        if ($kmRecorridos <= 0 || $litrosEvaluados <= 0) {
            return;
        }

        // El rendimiento se evalúa con los litros cargados en la carga actual,
        // conforme al método de tanque lleno validado en el reporte de combustible.
        $rendimiento = round($kmRecorridos / $litrosEvaluados, 2);

        // Guardar rendimiento
        Rendimiento::create([
            'carga_id' => $carga->id,
            'vehiculo_id' => $carga->vehiculo_id,
            'km_anterior' => $cargaAnterior->km_odometro,
            'km_recorridos' => $kmRecorridos,
            'rendimiento_km_l' => $rendimiento,
            'es_base' => false,
            'evaluado' => true,
        ]);

        // Comparar contra rendimiento óptimo
        $vehiculo = $carga->vehiculo;

        $tolerancia = $vehiculo->tolerancia_pct
            ?? ParametroSistema::where('clave', 'umbral_rendimiento_pct')->value('valor')
            ?? 0;

        $umbralMinimo = $vehiculo->rendimiento_optimo_km_l * (1 - ($tolerancia / 100));
        $umbralMaximo = $vehiculo->rendimiento_optimo_km_l * (1 + (self::UMBRAL_AUMENTO_ANORMAL_PCT / 100));

        $tipoAlerta = null;
        $umbralAplicado = null;

        if ($rendimiento < $umbralMinimo) {
            $tipoAlerta = 'bajo_rendimiento';
            $umbralAplicado = $umbralMinimo;
        } elseif ($rendimiento > $umbralMaximo) {
            $tipoAlerta = 'rendimiento_anormal_alto';
            $umbralAplicado = $umbralMaximo;
        }

        if (! $tipoAlerta) {
            return;
        }

        // Evitar alertas duplicadas por la misma carga
        if (AlertaRendimiento::where('carga_id', $carga->id)->exists()) {
            return;
        }

        // Responsable vigente: ordenar por fecha_inicio
        $responsableActivo = $vehiculo->responsables()
            ->where('activo', true)
            ->orderByDesc('fecha_inicio')
            ->first();

        AlertaRendimiento::create([
            'vehiculo_id' => $vehiculo->id,
            'responsable_user_id' => optional($responsableActivo)->responsable_user_id,
            'carga_id' => $carga->id,
            'tipo' => $tipoAlerta,
            'rendimiento_detectado' => $rendimiento,
            'rendimiento_optimo' => $vehiculo->rendimiento_optimo_km_l,
            'umbral_aplicado' => $umbralAplicado,
            'estatus' => 'Abierta',
            'fecha_alerta' => now(),
        ]);
    }

    protected function obtenerCargaAnterior(CargaCombustible $carga): ?CargaCombustible
    {
        return CargaCombustible::query()
            ->where('vehiculo_id', $carga->vehiculo_id)
            ->where(function ($query) use ($carga) {
                $query->where('fecha_carga', '<', $carga->fecha_carga)
                    ->orWhere(function ($subQuery) use ($carga) {
                        $subQuery->where('fecha_carga', $carga->fecha_carga)
                            ->where('id', '<', $carga->id);
                    });
            })
            ->orderedChronologicallyDesc()
            ->first();
    }

    protected function obtenerCargasDesde(CargaCombustible $carga): Collection
    {
        return CargaCombustible::query()
            ->where('vehiculo_id', $carga->vehiculo_id)
            ->where(function ($query) use ($carga) {
                $query->where('fecha_carga', '>', $carga->fecha_carga)
                    ->orWhere(function ($subQuery) use ($carga) {
                        $subQuery->where('fecha_carga', $carga->fecha_carga)
                            ->where('id', '>=', $carga->id);
                    });
            })
            ->orderedChronologically()
            ->get();
    }
}
