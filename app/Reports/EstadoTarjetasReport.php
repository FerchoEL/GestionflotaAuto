<?php

namespace App\Reports;

use App\Models\TarjetaCombustible;
use App\Models\VehiculoTarjeta;
use Illuminate\Database\Eloquent\Builder;

class EstadoTarjetasReport
{
    // Las relaciones operativas tarjetaActiva/vehiculoActivo usan el indicador activo.
    public static function vigentes(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public static function conflicto(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->has('vehiculoTarjetas', '>', 1, 'and', self::vigentes(...))
                ->orWhereHas('vehiculoTarjetas', fn (Builder $a) => self::vigentes($a)->where(function (Builder $a) {
                    $a->whereNotNull('fecha_fin')->orWhereDate('fecha_inicio', '>', today())
                        ->orWhereDoesntHave('vehiculo')
                        ->orWhereHas('vehiculo.tarjetas', fn (Builder $b) => self::vigentes($b)
                            ->whereColumn('vehiculo_tarjetas.tarjeta_combustible_id', '!=', 'tarjeta_combustibles.id'));
                }));
        });
    }

    public static function query(): Builder
    {
        return TarjetaCombustible::query()->withCount([
            'vehiculoTarjetas as vigentes_count' => self::vigentes(...),
        ])->withExists(['vehiculoTarjetas as conflicto' => fn (Builder $q) => $q->whereIn('tarjeta_combustible_id', self::conflicto(TarjetaCombustible::query())->select('id'))])
            ->with(['vehiculoTarjetas' => fn ($q) => $q->where('activo', true)->orderBy('id'),
                'vehiculoTarjetas.vehiculo.localidadActiva.localidad',
                'vehiculoTarjetas.vehiculo.cuentaAnaliticaActiva.cuentaAnalitica']);
    }

    public static function estado(TarjetaCombustible $tarjeta): string
    {
        return $tarjeta->conflicto ? 'POSIBLE DUPLICIDAD' : ($tarjeta->vigentes_count ? 'ASIGNADA' : 'SIN ASIGNAR');
    }

    public static function valores(TarjetaCombustible $tarjeta, string $path): string
    {
        return $tarjeta->vehiculoTarjetas->map(fn ($a) => data_get($a, $path))
            ->map(fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v)
            ->map(fn ($v) => $v === null || $v === '' ? 'SIN INFORMACIÓN' : (string) $v)->join(' | ');
    }

    public static function fila(TarjetaCombustible $t): array
    {
        return [self::valores($t, 'vehiculo.numero_economico'), self::valores($t, 'vehiculo.placas'),
            self::valores($t, 'vehiculo.marca'), self::valores($t, 'vehiculo.modelo'),
            self::valores($t, 'vehiculo.localidadActiva.localidad.nombre'), (string) $t->numero,
            self::valores($t, 'fecha_inicio'), self::valores($t, 'activo'),
            self::valores($t, 'vehiculo.cuentaAnaliticaActiva.cuentaAnalitica.nombre') ?: 'SIN INFORMACIÓN', self::estado($t)];
    }

    public static function resumen(): array
    {
        $total = TarjetaCombustible::count();
        $asignadas = TarjetaCombustible::whereHas('vehiculoTarjetas', self::vigentes(...))->count();

        return ['Total de tarjetas' => $total, 'Con asignación vigente' => $asignadas,
            'Sin asignación vigente' => $total - $asignadas,
            'Unidades con tarjeta' => VehiculoTarjeta::where('activo', true)->whereHas('vehiculo')->distinct()->count('vehiculo_id'),
            'Tarjetas con conflictos' => self::conflicto(TarjetaCombustible::query())->count()];
    }
}
