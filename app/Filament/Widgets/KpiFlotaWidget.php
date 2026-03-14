<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Vehiculo;
use App\Models\AlertaRendimiento;
use App\Models\CargaCombustible;
use App\Support\FlotaScope;
use Carbon\Carbon;

class KpiFlotaWidget extends BaseWidget
{
    protected function getCards(): array
    {
        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana = Carbon::now()->endOfWeek();

        // vehículos visibles para el usuario
        $vehiculosIds = FlotaScope::idsVehiculosUsuario();

        return [

            Card::make(
                'Vehículos Activos',
                Vehiculo::whereIn('id', $vehiculosIds)->count()
            ),

            Card::make(
                'Alertas Abiertas',
                AlertaRendimiento::whereIn('vehiculo_id', $vehiculosIds)
                    ->where('estatus', 'Abierta')
                    ->count()
            ),

            Card::make(
                'Litros Consumidos Semana',
                number_format(
                    CargaCombustible::whereIn('vehiculo_id', $vehiculosIds)
                        ->whereBetween('fecha_carga', [$inicioSemana, $finSemana])
                        ->sum('litros'),
                    2
                )
            ),

            Card::make(
                'Cargas Semana',
                CargaCombustible::whereIn('vehiculo_id', $vehiculosIds)
                    ->whereBetween('fecha_carga', [$inicioSemana, $finSemana])
                    ->count()
            ),
        ];
    }
}