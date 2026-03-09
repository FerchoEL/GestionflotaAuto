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
        static::creating(function ($registro) {

            // Cerrar tarjeta activa anterior del vehículo
            static::where('vehiculo_id', $registro->vehiculo_id)
                ->where('activo', true)
                ->update([
                    'activo' => false,
                    'fecha_fin' => now(),
                ]);

            // Cerrar tarjeta activa anterior de esa tarjeta
            static::where('tarjeta_combustible_id', $registro->tarjeta_combustible_id)
                ->where('activo', true)
                ->update([
                    'activo' => false,
                    'fecha_fin' => now(),
                ]);
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
}
