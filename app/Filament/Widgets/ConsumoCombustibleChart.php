<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\CargaCombustible;
use Illuminate\Support\Facades\DB;

class ConsumoCombustibleChart extends ChartWidget
{
    protected static ?string $heading = 'Consumo mensual de combustible';

    protected function getData(): array
    {

        $data = CargaCombustible::select(
            DB::raw('MONTH(fecha_carga) mes'),
            DB::raw('SUM(importe) total')
        )
        ->groupBy('mes')
        ->orderBy('mes')
        ->get();

        return [

            'datasets'=>[
                [
                    'label'=>'Gasto Combustible',
                    'data'=>$data->pluck('total'),
                ]
            ],

            'labels'=>$data->pluck('mes'),

        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}