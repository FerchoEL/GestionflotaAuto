<?php

namespace App\Services;

use App\Models\CargaCombustible;
use App\Models\Rendimiento;
use App\Models\AlertaRendimiento;
use App\Models\ParametroSistema;
use Illuminate\Support\Facades\DB;

class RendimientoService
{
    /**
     * Procesa una carga de combustible y calcula el rendimiento.
     */
    public function procesarCarga(CargaCombustible $carga): void
    {
        DB::transaction(function () use ($carga) {

            // Evitar reprocesos: si ya existe rendimiento para esta carga, no duplicar
            if (Rendimiento::where('carga_id', $carga->id)->exists()) {
                return;
            }

            // Buscar la carga anterior del mismo vehículo
            $cargaAnterior = CargaCombustible::where('vehiculo_id', $carga->vehiculo_id)
                ->where('id', '<', $carga->id)
                ->orderBy('id', 'desc')
                ->first();

            // Si no hay carga anterior, se marca como base
            if (!$cargaAnterior) {
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

            if ($kmRecorridos <= 0 || $carga->litros <= 0) {
                return;
            }

            // Calcular rendimiento
            $rendimiento = round($kmRecorridos / $carga->litros, 2);

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

            if ($rendimiento < $umbralMinimo) {

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
                    'rendimiento_detectado' => $rendimiento,
                    'rendimiento_optimo' => $vehiculo->rendimiento_optimo_km_l,
                    'umbral_aplicado' => $umbralMinimo,
                    'estatus' => 'Abierta',
                    'fecha_alerta' => now(),
                ]);
            }
        });
    }
}
