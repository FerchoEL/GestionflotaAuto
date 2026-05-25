<?php

namespace App\Models;

use App\Services\TarjetaMovimientoService;
use Illuminate\Database\Eloquent\Model;

class Fondeo extends Model
{
    protected $fillable = [
        'vehiculo_id',
        'tarjeta_combustible_id',
        'litros_fondeados',
        'importe_fondeado',
        'fecha_fondeado',
        'fondeado_por_user_id',
        'comentario',
    ];

    protected $casts = [
        'fecha_fondeado' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $fondeo): void {
            // Si ya se proporcionó una tarjeta, respetarla; sólo resolver desde vehículo
            // cuando no se haya asignado `tarjeta_combustible_id` explícitamente.
            if (! $fondeo->tarjeta_combustible_id && $fondeo->vehiculo_id) {
                $fondeo->tarjeta_combustible_id = app(TarjetaMovimientoService::class)
                    ->resolverTarjetaIdVehiculoEnFecha($fondeo->vehiculo_id, $fondeo->fecha_fondeado);
            }
        });

        static::saved(function (self $fondeo): void {
            app(TarjetaMovimientoService::class)->sincronizarFondeo($fondeo);
        });

        static::deleted(function (self $fondeo): void {
            app(TarjetaMovimientoService::class)->eliminarMovimientoDeOrigen($fondeo);
        });
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function tarjeta()
    {
        return $this->belongsTo(TarjetaCombustible::class, 'tarjeta_combustible_id');
    }

    public function fondeadoPor()
    {
        return $this->belongsTo(User::class, 'fondeado_por_user_id');
    }

    public function usuario()
    {
        return $this->fondeadoPor();
    }
}
