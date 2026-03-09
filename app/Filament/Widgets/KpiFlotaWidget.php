<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Vehiculo;
use App\Models\AlertaRendimiento;
use App\Models\CargaCombustible;
use Carbon\Carbon;

class KpiFlotaWidget extends BaseWidget
{
    protected function getCards(): array
    {
        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana = Carbon::now()->endOfWeek();

        return [
            Card::make('Vehículos Activos', Vehiculo::where('activo', true)->count()),

            Card::make('Alertas Abiertas', 
                AlertaRendimiento::where('estatus', 'Abierta')->count()
            ),

            Card::make('Litros Consumidos Semana', 
                CargaCombustible::whereBetween('fecha_carga', [$inicioSemana, $finSemana])
                    ->sum('litros')
            ),

            Card::make('Cargas Semana', 
                CargaCombustible::whereBetween('fecha_carga', [$inicioSemana, $finSemana])
                    ->count()
            ),
        ];
    }
}
