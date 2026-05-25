<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VehiculosExport implements FromCollection, WithHeadings
{
    public function __construct(protected Collection $vehiculos)
    {
    }

    public function collection()
    {
        return $this->vehiculos->map(function ($vehiculo) {
            return [
                $vehiculo->numero_economico,
                $vehiculo->placas,
                $vehiculo->marca,
                $vehiculo->modelo,
                $vehiculo->anio,
                $vehiculo->color,
                $vehiculo->tipoVehiculo?->nombre,
                $vehiculo->estatus?->nombre,
                $vehiculo->tipo_combustible,
                $vehiculo->transmision,
                $vehiculo->vin,
                $vehiculo->capacidad_tanque_litros,
                $vehiculo->rendimiento_optimo_km_l,
                $vehiculo->tolerancia_pct,
                $vehiculo->localidadActiva?->localidad?->nombre,
                $vehiculo->departamentoActivo?->departamento?->nombre,
                $vehiculo->usuarios_asignados_texto,
                $vehiculo->usuario_responsable_texto,
                $vehiculo->activo ? 'Activo' : 'Inactivo',
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
            'Anio',
            'Color',
            'Tipo de Vehiculo',
            'Estatus',
            'Tipo de Combustible',
            'Transmision',
            'VIN',
            'Capacidad Tanque Litros',
            'Rendimiento Optimo KM/L',
            'Tolerancia %',
            'Localidad',
            'Departamento',
            'Usuarios Asignados',
            'Usuario Responsable',
            'Activo',
        ];
    }
}
