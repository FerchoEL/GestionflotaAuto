<?php

namespace App\Reports;

use App\Models\TarjetaCombustible;
use App\Models\VehiculoTarjeta;
use Illuminate\Support\Collection;

/** Construye una fotografía de consulta; no modifica ningún modelo. */
class CatalogoTarjetasCombustibleReport
{
    public function build(): array
    {
        $asignaciones = VehiculoTarjeta::query()
            ->where('activo', true)
            ->whereNull('fecha_fin')
            ->whereDate('fecha_inicio', '<=', today())
            ->with([
                'vehiculo.estatus',
                'vehiculo.departamentoActivo.departamento',
                'vehiculo.localidadActiva.localidad',
                'vehiculo.responsableActivo.responsable',
                'vehiculo.cuentaAnaliticaActiva.cuentaAnalitica',
                'tarjeta',
            ])
            ->orderBy('tarjeta_combustible_id')
            ->orderBy('fecha_inicio')
            ->orderBy('id')
            ->get();

        $porTarjeta = $asignaciones->groupBy('tarjeta_combustible_id');
        $porUnidad = $asignaciones->groupBy('vehiculo_id');

        $filas = TarjetaCombustible::query()
            ->orderBy('id')
            ->get()
            ->map(fn (TarjetaCombustible $tarjeta): array => $this->fila(
                $tarjeta,
                $porTarjeta->get($tarjeta->id, collect()),
                $porUnidad,
            ));

        return [
            'todas' => $filas,
            'asignadas' => $filas->where('indicador_asignacion', 'Asignada')->values(),
            'incidencias' => $filas->filter(fn (array $fila): bool => $fila['clasificacion'] !== 'ASIGNADA')->values(),
            'resumen' => [
                'total' => $filas->count(),
                'asignadas' => $filas->where('indicador_asignacion', 'Asignada')->count(),
                'sin_asignar' => $filas->where('indicador_asignacion', 'Sin asignar')->count(),
                'incidencias' => $filas->filter(fn (array $fila): bool => $fila['clasificacion'] === 'POSIBLE DUPLICIDAD')->count(),
            ],
        ];
    }

    private function fila(TarjetaCombustible $tarjeta, Collection $asignaciones, Collection $porUnidad): array
    {
        $vehiculos = $asignaciones->pluck('vehiculo')->filter();
        $incidencias = [];

        if ($asignaciones->count() > 1) {
            $incidencias[] = 'POSIBLE DUPLICIDAD: tarjeta con '.$asignaciones->count().' asignaciones vigentes';
        }

        foreach ($vehiculos->unique('id') as $vehiculo) {
            $tarjetasDeUnidad = $porUnidad->get($vehiculo->id, collect());

            if ($tarjetasDeUnidad->unique('tarjeta_combustible_id')->count() > 1) {
                $numeros = $tarjetasDeUnidad->pluck('tarjeta.numero')->filter()->unique()->values()->join(', ');
                $incidencias[] = "POSIBLE DUPLICIDAD: unidad {$vehiculo->numero_economico} con varias tarjetas vigentes ({$numeros})";
            }
        }

        $asignada = $asignaciones->isNotEmpty();

        return [
            'id_tarjeta' => $tarjeta->id,
            'numero_tarjeta' => (string) $tarjeta->numero,
            'estado_tarjeta' => $tarjeta->activo ? 'Activa' : 'Inactiva',
            'fecha_registro' => $tarjeta->created_at?->format('Y-m-d H:i:s'),
            'fecha_ultima_actualizacion' => $tarjeta->updated_at?->format('Y-m-d H:i:s'),
            'indicador_asignacion' => $asignada ? 'Asignada' : 'Sin asignar',
            'clasificacion' => ! $asignada ? 'SIN ASIGNAR' : ($incidencias === [] ? 'ASIGNADA' : 'POSIBLE DUPLICIDAD'),
            'unidades_involucradas' => $this->valores($vehiculos, fn ($vehiculo) => $vehiculo->numero_economico),
            'numero_unidad' => $this->valores($vehiculos, fn ($vehiculo) => $vehiculo->numero_economico),
            'marca' => $this->valores($vehiculos, fn ($vehiculo) => $vehiculo->marca),
            'modelo' => $this->valores($vehiculos, fn ($vehiculo) => $vehiculo->modelo),
            'anio' => $this->valores($vehiculos, fn ($vehiculo) => $vehiculo->anio),
            'placas' => $this->valores($vehiculos, fn ($vehiculo) => $vehiculo->placas),
            'vin' => $this->valores($vehiculos, fn ($vehiculo) => $vehiculo->vin),
            'departamento' => $this->valores($vehiculos, fn ($vehiculo) => $vehiculo->departamentoActivo?->departamento?->nombre),
            'localidad' => $this->valores($vehiculos, fn ($vehiculo) => $vehiculo->localidadActiva?->localidad?->nombre),
            'responsable_actual' => $this->valores($vehiculos, fn ($vehiculo) => $vehiculo->responsableActivo?->responsable?->name),
            'cuenta_analitica' => $this->valores($vehiculos, fn ($vehiculo) => $vehiculo->cuentaAnaliticaActiva?->cuentaAnalitica?->nombre),
            'estatus_unidad' => $this->valores($vehiculos, fn ($vehiculo) => $vehiculo->estatus?->nombre),
            'incidencias' => implode(' | ', array_unique($incidencias)),
        ];
    }

    private function valores(Collection $vehiculos, callable $valor): string
    {
        return $vehiculos->map($valor)->filter(fn ($valor) => $valor !== null && $valor !== '')
            ->unique()->values()->join(' | ');
    }
}
