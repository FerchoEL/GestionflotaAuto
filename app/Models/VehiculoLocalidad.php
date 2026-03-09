<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoLocalidad extends Model
{
    protected $fillable = [
        'vehiculo_id',
        'localidad_id',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'asignado_por_user_id',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function localidad()
    {
        return $this->belongsTo(Localidad::class);
    }

    protected static function booted()
    {
        static::creating(function ($registro) {
            static::where('vehiculo_id', $registro->vehiculo_id)
                ->where('activo', true)
                ->update([
                    'activo' => false,
                    'fecha_fin' => now(),
                ]);
        });
    }
    
}
