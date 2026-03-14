<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\CargaCombustible;
use App\Support\FlotaScope;
use Carbon\Carbon;

class ConsumoMensualChart extends ChartWidget
{
    protected static ?string $heading = 'Consumo mensual de combustible';

    protected function getData(): array
    {
        $vehiculosIds = FlotaScope::idsVehiculosUsuario();

        $labels = [];
        $data = [];

        for ($i = 5; $i >= 0; $i--) {

            $mes = Carbon::now()->subMonths($i);

            $inicio = $mes->copy()->startOfMonth();
            $fin = $mes->copy()->endOfMonth();

            $labels[] = $mes->format('M Y');

            $data[] = CargaCombustible::whereIn('vehiculo_id', $vehiculosIds)
                ->whereBetween('fecha_carga', [$inicio, $fin])
                ->sum('litros');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Litros',
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}