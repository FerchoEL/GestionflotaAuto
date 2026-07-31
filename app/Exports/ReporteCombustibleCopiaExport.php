<?php

namespace App\Exports;

use App\Services\ReporteCombustibleCopiaService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReporteCombustibleCopiaExport implements FromCollection, WithHeadings
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $service = app(ReporteCombustibleCopiaService::class);
        $totales = $service->totalesReporte($this->filters);
        $datos = $service->datosReporte($this->filters);

        return ($this->filters['vehiculo'] ?? null)
            ? $this->coleccionDetalle($datos, $totales)
            : $this->coleccionResumen($datos, $totales);
    }

    public function headings(): array
    {
        return ($this->filters['vehiculo'] ?? null)
            ? [
                'Numero Economico',
                'Placa',
                'Marca',
                'Modelo',
                'Localidad',
                'Departamento',
                'Usuarios asignados',
                'Usuario responsable',
                'Fecha',
                'Tarjeta',
                'Odometro',
                'Odometro Anterior',
                'KM Recorridos',
                'Litros Cargados',
                'Rendimiento Real',
                'Rendimiento Optimo',
                'Precio/L',
                'Importe',
                'Cuenta Analitica',
            ]
            : [
                'Numero Economico',
                'Placa',
                'Marca',
                'Modelo',
                'Localidad',
                'Departamento',
                'Usuarios asignados',
                'Usuario responsable',
                'Tarjeta activa',
                'Odometro inicial',
                'Odometro final',
                'KM Recorridos',
                'Litros Cargados',
                'Rendimiento Real',
                'Rendimiento Optimo',
                'Importe',
            ];
    }

    private function coleccionResumen($datos, object $totales)
    {
        $filas = $datos->map(fn ($v) => [
            $v->numero_economico,
            $v->placas,
            $v->marca,
            $v->modelo,
            $v->localidad,
            $v->departamento,
            $v->usuarios_asignados,
            $v->usuario_responsable,
            $v->tarjeta,
            $v->odometro_inicial,
            $v->odometro_final,
            $v->km_recorridos,
            $v->litros_cargados,
            $v->rendimiento_real,
            $v->rendimiento_optimo_km_l,
            $v->importe,
        ]);

        return $filas->push([
            'TOTALES', null, null, null, null, null, null, null, null, null, null,
            $totales->km,
            $totales->litros,
            $totales->rendimiento,
            null,
            $totales->importe,
        ]);
    }

    private function coleccionDetalle($datos, object $totales)
    {
        $filas = $datos->map(fn ($c) => [
            $c->vehiculo?->numero_economico,
            $c->vehiculo?->placas,
            $c->vehiculo?->marca,
            $c->vehiculo?->modelo,
            $c->vehiculo?->localidadActiva?->localidad?->nombre,
            $c->vehiculo?->departamentoActivo?->departamento?->nombre,
            $c->vehiculo?->usuarios_asignados_texto,
            $c->vehiculo?->usuario_responsable_texto,
            $c->fecha_carga,
            $c->vehiculo?->tarjetaActiva?->tarjeta?->numero,
            $c->km_odometro,
            $c->odometro_anterior_reporte,
            $c->km_recorridos_reporte,
            $c->litros,
            $c->rendimiento_real_reporte,
            $c->vehiculo?->rendimiento_optimo_km_l,
            $c->precio_litro,
            $c->importe,
            $c->cuentaAnalitica?->nombre,
        ]);

        return $filas->push([
            'TOTALES', null, null, null, null, null, null, null, null, null, null, null,
            $totales->km,
            $totales->litros,
            $totales->rendimiento,
            null,
            null,
            $totales->importe,
            null,
        ]);
    }
}
