<?php

namespace App\Exports;

use App\Services\ReporteCombustibleCopiaService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReporteCombustibleCopiaExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return app(ReporteCombustibleCopiaService::class)
            ->cargasConRendimientoReal($this->filters)
            ->map(function ($c) {
                return [
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
                ];
            });
    }

    public function headings(): array
    {
        return [
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
            'Litros Cargados/Comprados',
            'Rendimiento Real',
            'Rendimiento Optimo',
            'Precio/L',
            'Importe',
            'Cuenta Analitica',
        ];
    }
}
