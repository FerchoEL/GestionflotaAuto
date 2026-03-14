<?php

namespace App\Exports;

use App\Models\CargaCombustible;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReporteCombustibleExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = CargaCombustible::query()
            ->with(['vehiculo','cuentaAnalitica','rendimiento']);

        if($this->filters['vehiculo'])
            $query->where('vehiculo_id',$this->filters['vehiculo']);

        if($this->filters['cuenta'])
            $query->where('cuenta_analitica_id',$this->filters['cuenta']);

        if($this->filters['inicio'])
            $query->whereDate('fecha_carga','>=',$this->filters['inicio']);

        if($this->filters['fin'])
            $query->whereDate('fecha_carga','<=',$this->filters['fin']);

        return $query->get()->map(function($c){

            return [

                $c->fecha_carga,
                $c->vehiculo?->placas,
                $c->vehiculo?->numero_economico,
                $c->km_odometro,
                $c->rendimiento?->km_recorridos,
                $c->litros,
                $c->rendimiento?->rendimiento_km_l,
                $c->precio_litro,
                $c->importe,
                $c->cuentaAnalitica?->nombre

            ];

        });

    }

    public function headings(): array
    {
        return [

            'Fecha',
            'Vehiculo',
            'Numero Economico',
            'KM',
            'KM Recorridos',
            'Litros',
            'Rendimiento',
            'Precio/L',
            'Importe',
            'Cuenta Analitica'

        ];
    }
}