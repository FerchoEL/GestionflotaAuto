<?php

namespace App\Services;

use App\Models\CargaCombustible;
use App\Models\Fondeo;
use App\Models\TarjetaCombustible;
use App\Models\TarjetaSaldoMovimiento;
use App\Models\Vehiculo;
use App\Models\VehiculoFondeoConfig;
use Illuminate\Support\Carbon;

class TarjetaSaldoService
{
    public function obtenerAsignadoLitrosVehiculo(Vehiculo $vehiculo): float
    {
        return (float) (
            VehiculoFondeoConfig::query()
                ->where('vehiculo_id', $vehiculo->id)
                ->where('activo', true)
                ->value('litros_asignados') ?? 0
        );
    }

    public function obtenerFondeadoLitrosVehiculo(Vehiculo $vehiculo): float
    {
        return (float) Fondeo::query()
            ->where('vehiculo_id', $vehiculo->id)
            ->sum('litros_fondeados');
    }

    public function obtenerConsumidoLitrosVehiculo(Vehiculo $vehiculo): float
    {
        return (float) CargaCombustible::query()
            ->where('vehiculo_id', $vehiculo->id)
            ->sum('litros');
    }

    public function obtenerSaldoBaseLitrosVehiculo(Vehiculo $vehiculo): float
    {
        return $this->obtenerFondeadoLitrosVehiculo($vehiculo)
            - $this->obtenerConsumidoLitrosVehiculo($vehiculo);
    }

    public function obtenerUltimoPrecioLitroVehiculo(Vehiculo $vehiculo): float
    {
        $precioCarga = CargaCombustible::query()
            ->where('vehiculo_id', $vehiculo->id)
            ->whereNotNull('precio_litro')
            ->where('precio_litro', '>', 0)
            ->orderByDesc('fecha_carga')
            ->value('precio_litro');

        if ($precioCarga !== null) {
            return (float) $precioCarga;
        }

        $ultimoFondeo = Fondeo::query()
            ->where('vehiculo_id', $vehiculo->id)
            ->where('litros_fondeados', '>', 0)
            ->where('importe_fondeado', '>', 0)
            ->orderByDesc('fecha_fondeado')
            ->first(['litros_fondeados', 'importe_fondeado']);

        if (! $ultimoFondeo) {
            return 0.0;
        }

        return round(
            (float) $ultimoFondeo->importe_fondeado / (float) $ultimoFondeo->litros_fondeados,
            2
        );
    }

    public function obtenerTarjetaActivaVehiculo(Vehiculo $vehiculo): ?TarjetaCombustible
    {
        return $vehiculo->tarjetaActiva?->tarjeta;
    }

    public function obtenerVehiculoActivoTarjeta(TarjetaCombustible $tarjeta): ?Vehiculo
    {
        return $tarjeta->vehiculoActivo?->vehiculo;
    }

    public function obtenerMovimientosOneCardPesosTarjeta(TarjetaCombustible $tarjeta): float
    {
        return (float) TarjetaSaldoMovimiento::query()
            ->where('tarjeta_combustible_id', $tarjeta->id)
            ->sum('monto');
    }

    public function obtenerSaldoBasePesosTarjeta(TarjetaCombustible $tarjeta): float
    {
        $vehiculo = $this->obtenerVehiculoActivoTarjeta($tarjeta);

        if (! $vehiculo) {
            return 0.0;
        }

        return round(
            $this->obtenerSaldoBaseLitrosVehiculo($vehiculo) * $this->obtenerUltimoPrecioLitroVehiculo($vehiculo),
            2
        );
    }

    public function obtenerFondoObjetivoPesosTarjeta(TarjetaCombustible $tarjeta): float
    {
        $vehiculo = $this->obtenerVehiculoActivoTarjeta($tarjeta);

        if (! $vehiculo) {
            return 0.0;
        }

        return round(
            $this->obtenerAsignadoLitrosVehiculo($vehiculo) * $this->obtenerUltimoPrecioLitroVehiculo($vehiculo),
            2
        );
    }

    public function obtenerSaldoFinancieroPesosTarjeta(TarjetaCombustible $tarjeta): float
    {
        return round(
            $this->obtenerSaldoBasePesosTarjeta($tarjeta)
            + $this->obtenerMovimientosOneCardPesosTarjeta($tarjeta),
            2
        );
    }

    public function obtenerImpactoOneCardLitrosVehiculo(Vehiculo $vehiculo): float
    {
        $tarjeta = $this->obtenerTarjetaActivaVehiculo($vehiculo);

        if (! $tarjeta) {
            return 0.0;
        }

        $litrosImpactados = TarjetaSaldoMovimiento::query()
            ->where('tarjeta_combustible_id', $tarjeta->id)
            ->orderBy('fecha_movimiento')
            ->orderBy('id')
            ->get()
            ->sum(function (TarjetaSaldoMovimiento $movimiento) use ($vehiculo): float {
                $precio = $this->obtenerPrecioLitroVehiculoEnFecha(
                    $vehiculo,
                    $movimiento->fecha_movimiento,
                );

                if ($precio <= 0) {
                    return 0.0;
                }

                return (float) $movimiento->monto / $precio;
            });

        return round((float) $litrosImpactados, 2);
    }

    public function obtenerSaldoDisponibleLitrosVehiculo(Vehiculo $vehiculo): float
    {
        return round(
            $this->obtenerSaldoBaseLitrosVehiculo($vehiculo) + $this->obtenerImpactoOneCardLitrosVehiculo($vehiculo),
            2
        );
    }

    public function obtenerPendienteLitrosVehiculo(Vehiculo $vehiculo): float
    {
        return max(
            round(
                $this->obtenerAsignadoLitrosVehiculo($vehiculo)
                - $this->obtenerSaldoDisponibleLitrosVehiculo($vehiculo),
                2
            ),
            0
        );
    }

    public function obtenerPorcentajeVehiculo(Vehiculo $vehiculo): int
    {
        $asignado = $this->obtenerAsignadoLitrosVehiculo($vehiculo);

        if ($asignado <= 0) {
            return 0;
        }

        return (int) round(($this->obtenerSaldoDisponibleLitrosVehiculo($vehiculo) / $asignado) * 100, 0);
    }

    public function obtenerColorSemaforoVehiculo(Vehiculo $vehiculo): string
    {
        $porcentaje = $this->obtenerPorcentajeVehiculo($vehiculo);
        $saldo = $this->obtenerSaldoDisponibleLitrosVehiculo($vehiculo);

        if ($saldo <= 0) {
            return 'danger';
        }

        if ($porcentaje < 40) {
            return 'danger';
        }

        if ($porcentaje < 70) {
            return 'warning';
        }

        return 'success';
    }

    public function obtenerIconoSemaforoVehiculo(Vehiculo $vehiculo): string
    {
        $porcentaje = $this->obtenerPorcentajeVehiculo($vehiculo);
        $saldo = $this->obtenerSaldoDisponibleLitrosVehiculo($vehiculo);

        if ($saldo <= 0) {
            return 'heroicon-o-exclamation-triangle';
        }

        if ($porcentaje < 40) {
            return 'heroicon-o-exclamation-circle';
        }

        if ($porcentaje < 70) {
            return 'heroicon-o-exclamation-triangle';
        }

        return 'heroicon-o-check-circle';
    }

    public function obtenerMontoReposicionPesosTarjeta(TarjetaCombustible $tarjeta): float
    {
        return max(
            round(
                $this->obtenerFondoObjetivoPesosTarjeta($tarjeta)
                - $this->obtenerSaldoFinancieroPesosTarjeta($tarjeta),
                2
            ),
            0
        );
    }

    protected function obtenerPrecioLitroVehiculoEnFecha(Vehiculo $vehiculo, Carbon|string $fecha): float
    {
        $fecha = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);

        $precioCarga = CargaCombustible::query()
            ->where('vehiculo_id', $vehiculo->id)
            ->whereNotNull('precio_litro')
            ->where('precio_litro', '>', 0)
            ->where('fecha_carga', '<=', $fecha)
            ->orderByDesc('fecha_carga')
            ->orderByDesc('id')
            ->value('precio_litro');

        if ($precioCarga !== null) {
            return (float) $precioCarga;
        }

        $ultimoFondeo = Fondeo::query()
            ->where('vehiculo_id', $vehiculo->id)
            ->where('litros_fondeados', '>', 0)
            ->where('importe_fondeado', '>', 0)
            ->where('fecha_fondeado', '<=', $fecha)
            ->orderByDesc('fecha_fondeado')
            ->orderByDesc('id')
            ->first(['litros_fondeados', 'importe_fondeado']);

        if ($ultimoFondeo) {
            return round(
                (float) $ultimoFondeo->importe_fondeado / (float) $ultimoFondeo->litros_fondeados,
                2
            );
        }

        return $this->obtenerUltimoPrecioLitroVehiculo($vehiculo);
    }
}
