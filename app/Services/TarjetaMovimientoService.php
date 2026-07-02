<?php

namespace App\Services;

use App\Models\CargaCombustible;
use App\Models\Fondeo;
use App\Models\TarjetaSaldoMovimiento;
use App\Models\VehiculoTarjeta;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TarjetaMovimientoService
{
    public function resolverTarjetaIdVehiculoEnFecha(?int $vehiculoId, CarbonInterface|string|null $fecha): ?int
    {
        if (! $vehiculoId || ! $fecha) {
            return null;
        }

        $fecha = $fecha instanceof CarbonInterface ? $fecha : Carbon::parse($fecha);
        $fechaReferencia = $fecha->toDateString();

        return VehiculoTarjeta::query()
            ->where('vehiculo_id', $vehiculoId)
            ->whereDate('fecha_inicio', '<=', $fechaReferencia)
            ->where(function ($query) use ($fechaReferencia): void {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $fechaReferencia);
            })
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->value('tarjeta_combustible_id');
    }

    public function sincronizarFondeo(Fondeo $fondeo): void
    {
        $this->sincronizarMovimiento(
            origen: $fondeo,
            tarjetaCombustibleId: $fondeo->tarjeta_combustible_id,
            fechaMovimiento: $fondeo->fecha_fondeado,
            tipo: 'fondeo_tarjeta',
            monto: abs((float) $fondeo->importe_fondeado),
            registradoPorUserId: $fondeo->fondeado_por_user_id,
            referencia: 'Fondeo #' . $fondeo->id,
            comentario: $fondeo->comentario,
        );
    }

    public function sincronizarCarga(CargaCombustible $carga): void
    {
        $registradoPor = $carga->registrada_por_user_id ?: $carga->user_id;

        $this->sincronizarMovimiento(
            origen: $carga,
            tarjetaCombustibleId: $carga->tarjeta_combustible_id,
            fechaMovimiento: $carga->fecha_carga,
            tipo: 'consumo_combustible',
            monto: -abs($this->obtenerMontoOficialCarga($carga)),
            registradoPorUserId: $registradoPor,
            referencia: 'Carga #' . $carga->id,
            comentario: $carga->es_extemporanea
                ? 'Consumo registrado en carga extemporanea.'
                : 'Consumo registrado en carga de combustible.',
        );
    }

    public function eliminarMovimientoDeOrigen(Model $origen): void
    {
        TarjetaSaldoMovimiento::query()
            ->where('origen_tipo', $origen::class)
            ->where('origen_id', $origen->getKey())
            ->delete();
    }

    protected function obtenerMontoOficialCarga(CargaCombustible $carga): float
    {
        $importe = (float) ($carga->importe ?? 0);

        if ($importe > 0) {
            return round($importe, 2);
        }

        $litros = (float) $carga->litros;
        $precioLitro = (float) $carga->precio_litro;

        if ($litros > 0 && $precioLitro > 0) {
            return round($litros * $precioLitro, 2);
        }

        return 0.0;
    }

    public function aplicaFechaCorte(CarbonInterface|string|null $fecha): bool
    {
        if (! $fecha) {
            return false;
        }

        $fecha = $fecha instanceof CarbonInterface ? $fecha : Carbon::parse($fecha);
        $fechaCorte = config('combustible.fecha_corte_movimientos_tarjeta');

        if (! $fechaCorte) {
            return true;
        }

        return $fecha->greaterThanOrEqualTo(Carbon::parse($fechaCorte));
    }

    protected function sincronizarMovimiento(
        Model $origen,
        ?int $tarjetaCombustibleId,
        CarbonInterface|string|null $fechaMovimiento,
        string $tipo,
        float $monto,
        ?int $registradoPorUserId,
        ?string $referencia,
        ?string $comentario,
    ): void {
        if (! $fechaMovimiento || ! $this->aplicaFechaCorte($fechaMovimiento) || ! $tarjetaCombustibleId || abs($monto) < 0.01) {
            $this->eliminarMovimientoDeOrigen($origen);

            return;
        }

        TarjetaSaldoMovimiento::query()->updateOrCreate(
            [
                'origen_tipo' => $origen::class,
                'origen_id' => $origen->getKey(),
            ],
            [
                'tarjeta_combustible_id' => $tarjetaCombustibleId,
                'tipo' => $tipo,
                'monto' => round($monto, 2),
                'fecha_movimiento' => $fechaMovimiento,
                'registrado_por_user_id' => $registradoPorUserId,
                'referencia' => $referencia,
                'comentario' => $comentario,
                'tarjeta_destino_id' => null,
            ]
        );
    }
}
