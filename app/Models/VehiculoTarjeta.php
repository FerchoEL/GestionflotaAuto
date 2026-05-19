<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoTarjeta extends Model
{
    protected $fillable = [
        'vehiculo_id',
        'tarjeta_combustible_id',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($registro) {
            if (! $registro->activo || $registro->fecha_fin !== null) {
                $registro->activo = false;

                return;
            }

            static::where('vehiculo_id', $registro->vehiculo_id)
                ->where('activo', true)
                ->when($registro->exists, fn ($query) => $query->whereKeyNot($registro->getKey()))
                ->update([
                    'activo' => false,
                    'fecha_fin' => now()->toDateString(),
                ]);

            static::where('tarjeta_combustible_id', $registro->tarjeta_combustible_id)
                ->where('activo', true)
                ->when($registro->exists, fn ($query) => $query->whereKeyNot($registro->getKey()))
                ->update([
                    'activo' => false,
                    'fecha_fin' => now()->toDateString(),
                ]);
        });
    }

    public function tarjeta()
    {
        return $this->belongsTo(TarjetaCombustible::class, 'tarjeta_combustible_id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
}
