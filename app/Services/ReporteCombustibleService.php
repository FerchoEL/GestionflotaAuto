<?php

namespace App\Services;

use App\Models\CargaCombustible;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Support\FlotaScope;

class ReporteCombustibleService
{
    public function rangoPorDefecto(): array
    {
        $fechaInicio = CargaCombustible::query()->min('fecha_carga');

        return [
            'inicio' => $fechaInicio
                ? Carbon::parse($fechaInicio)->toDateString()
                : now()->startOfMonth()->toDateString(),
            'fin' => now()->toDateString(),
        ];
    }

    private function normalizarFiltros(array $filters): array
    {
        return array_merge([
            'vehiculo' => null,
            'departamento' => null,
            'localidad' => null,
            'cuenta' => null,
            'tipo_combustible' => null,
            'inicio' => null,
            'fin' => null,
        ], $filters, $this->rangoPorDefecto());
    }

    public function historialVehiculoConRendimientoReal(
        int $vehiculoId,
        int $perPage = 10,
        string $pageName = 'page'
    ): LengthAwarePaginator {
        $base = CargaCombustible::query()
            ->where('carga_combustibles.vehiculo_id', $vehiculoId)
            ->select('carga_combustibles.*')
            ->selectRaw('
                LAG(carga_combustibles.fecha_carga) OVER (
                    PARTITION BY carga_combustibles.vehiculo_id
                    ORDER BY carga_combustibles.fecha_carga, carga_combustibles.id
                ) as fecha_carga_anterior_reporte
            ')
            ->selectRaw('
                LAG(carga_combustibles.km_odometro) OVER (
                    PARTITION BY carga_combustibles.vehiculo_id
                    ORDER BY carga_combustibles.fecha_carga, carga_combustibles.id
                ) as odometro_anterior_reporte
            ')
            ->selectRaw('
                LAG(carga_combustibles.litros) OVER (
                    PARTITION BY carga_combustibles.vehiculo_id
                    ORDER BY carga_combustibles.fecha_carga, carga_combustibles.id
                ) as litros_consumo_reporte
            ');

        $paginator = DB::query()
            ->fromSub($base, 'base')
            ->orderByDesc('base.fecha_carga')
            ->orderByDesc('base.id')
            ->paginate($perPage, ['*'], $pageName);

        $paginator->setCollection(
            $paginator->getCollection()->map(function ($row) {
                $odometroAnterior = $row->odometro_anterior_reporte !== null
                    ? (float) $row->odometro_anterior_reporte
                    : null;

                $kmRecorridos = $odometroAnterior !== null && $row->km_odometro > $odometroAnterior
                    ? (float) $row->km_odometro - $odometroAnterior
                    : null;

                $litrosConsumo = $row->litros_consumo_reporte !== null
                    ? (float) $row->litros_consumo_reporte
                    : null;

                $row->km_recorridos_reporte = $kmRecorridos;
                $row->litros_consumo_reporte = $litrosConsumo;
                $row->rendimiento_real_reporte = $kmRecorridos !== null && $litrosConsumo > 0
                    ? round($kmRecorridos / $litrosConsumo, 2)
                    : null;

                return $row;
            })
        );

        return $paginator;
    }

    public function cargasConRendimientoReal(array $filters): Collection
    {
        $filters = $this->normalizarFiltros($filters);

        $rows = $this->queryRendimientoReal($filters)
            ->orderByDesc('base.fecha_carga')
            ->orderByDesc('base.id')
            ->get()
            ->keyBy('id');

        if ($rows->isEmpty()) {
            return collect();
        }

        return CargaCombustible::query()
            ->with([
                'vehiculo.marcaVehiculo',
                'vehiculo.choferes.chofer',
                'vehiculo.responsableActivo.responsable',
                'vehiculo.localidadActiva.localidad',
                'vehiculo.tarjetaActiva.tarjeta',
                'vehiculo.departamentoActivo.departamento',
                'cuentaAnalitica',
            ])
            ->whereIn('id', $rows->keys())
            ->orderByDesc('fecha_carga')
            ->orderByDesc('id')
            ->get()
            ->map(function (CargaCombustible $carga) use ($rows) {
                $row = $rows->get($carga->id);

                $odometroAnterior = $row->odometro_anterior_reporte !== null
                    ? (float) $row->odometro_anterior_reporte
                    : null;

                $kmRecorridos = $odometroAnterior !== null && $carga->km_odometro > $odometroAnterior
                    ? (float) $carga->km_odometro - $odometroAnterior
                    : null;

                $litrosConsumo = $row->litros_consumo_reporte !== null
                    ? (float) $row->litros_consumo_reporte
                    : null;

                $rendimiento = $kmRecorridos !== null && $litrosConsumo > 0
                    ? round($kmRecorridos / $litrosConsumo, 2)
                    : null;

                $carga->setAttribute('odometro_anterior_reporte', $odometroAnterior);
                $carga->setAttribute('litros_consumo_reporte', $litrosConsumo);
                $carga->setAttribute('km_recorridos_reporte', $kmRecorridos);
                $carga->setAttribute('rendimiento_real_reporte', $rendimiento);
                $carga->setAttribute('fecha_carga_anterior_reporte', $row->fecha_carga_anterior_reporte);

                return $carga;
            });
    }

    public function resumenVehiculos(array $filters): Collection
    {
        return $this->cargasConRendimientoReal($filters)
            ->groupBy('vehiculo_id')
            ->map(function (Collection $cargas) {
                $primeraCarga = $cargas->first();
                $vehiculo = $primeraCarga->vehiculo;
                $km = $cargas->sum(fn ($carga) => (float) ($carga->km_recorridos_reporte ?? 0));
                $litrosConsumo = $cargas->sum(fn ($carga) => (float) ($carga->litros_consumo_reporte ?? 0));

                return (object) [
                    'vehiculo_id' => $vehiculo?->id,
                    'placas' => $vehiculo?->placas,
                    'numero_economico' => $vehiculo?->numero_economico,
                    'marca' => $vehiculo?->marca,
                    'modelo' => $vehiculo?->modelo,
                    'departamento' => $vehiculo?->departamentoActivo?->departamento?->nombre,
                    'localidad' => $vehiculo?->localidadActiva?->localidad?->nombre,
                    'usuarios_asignados' => $vehiculo?->usuarios_asignados_texto,
                    'usuario_responsable' => $vehiculo?->usuario_responsable_texto,
                    'rendimiento_optimo_km_l' => $vehiculo?->rendimiento_optimo_km_l,
                    'km_recorridos' => $km,
                    'litros' => $litrosConsumo,
                    'litros_cargados' => $cargas->sum(fn ($carga) => (float) $carga->litros),
                    'importe' => $cargas->sum(fn ($carga) => (float) $carga->importe),
                ];
            })
            ->values();
    }

    private function queryRendimientoReal(array $filters)
    {
        $filters = $this->normalizarFiltros($filters);

        // Se calcula la ventana antes del filtro inicial para que la primera
        // carga del periodo pueda tomar la carga inmediata anterior.
        $base = CargaCombustible::query()
            ->select('carga_combustibles.id')
            ->addSelect('carga_combustibles.fecha_carga')
            ->addSelect('carga_combustibles.cuenta_analitica_id')
            ->selectRaw('
                LAG(carga_combustibles.id) OVER (
                    PARTITION BY carga_combustibles.vehiculo_id
                    ORDER BY carga_combustibles.fecha_carga, carga_combustibles.id
                ) as carga_anterior_id
            ')
            ->selectRaw('
                LAG(carga_combustibles.fecha_carga) OVER (
                    PARTITION BY carga_combustibles.vehiculo_id
                    ORDER BY carga_combustibles.fecha_carga, carga_combustibles.id
                ) as fecha_carga_anterior_reporte
            ')
            ->selectRaw('
                LAG(carga_combustibles.km_odometro) OVER (
                    PARTITION BY carga_combustibles.vehiculo_id
                    ORDER BY carga_combustibles.fecha_carga, carga_combustibles.id
                ) as odometro_anterior_reporte
            ')
            ->selectRaw('
                LAG(carga_combustibles.litros) OVER (
                    PARTITION BY carga_combustibles.vehiculo_id
                    ORDER BY carga_combustibles.fecha_carga, carga_combustibles.id
                ) as litros_consumo_reporte
            ');

        $this->aplicarFiltrosSinFechaInicio($base, $filters);

        return DB::query()
            ->fromSub($base, 'base')
            ->whereDate('base.fecha_carga', '>=', $filters['inicio'])
            ->whereDate('base.fecha_carga', '<=', $filters['fin'])
            ->when($filters['cuenta'], function ($query) use ($filters) {
                $query->where('base.cuenta_analitica_id', $filters['cuenta']);
            })
            ->select('base.*');
    }

    private function aplicarFiltrosSinFechaInicio(Builder $query, array $filters): void
    {
        $query->whereDate('carga_combustibles.fecha_carga', '<=', $filters['fin']);

        if (auth()->check() && ! auth()->user()->hasAnyRole(['admin', 'activos', 'fondeo'])) {
            $query->whereIn('carga_combustibles.vehiculo_id', FlotaScope::idsVehiculosUsuario());
        }

        if ($filters['vehiculo']) {
            $query->where('carga_combustibles.vehiculo_id', $filters['vehiculo']);
        }

        if ($filters['tipo_combustible']) {
            $query->whereHas('vehiculo', function ($q) use ($filters) {
                $q->where('tipo_combustible', $filters['tipo_combustible']);
            });
        }

        if ($filters['departamento']) {
            $query->whereHas('vehiculo.departamentoActivo', function ($q) use ($filters) {
                $q->where('departamento_id', $filters['departamento']);
            });
        }

        if ($filters['localidad']) {
            $query->whereHas('vehiculo.localidadActiva', function ($q) use ($filters) {
                $q->where('localidad_id', $filters['localidad']);
            });
        }
    }
}
