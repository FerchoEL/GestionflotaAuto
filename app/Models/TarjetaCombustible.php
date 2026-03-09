<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarjetaCombustible extends Model
{
    protected $fillable = [
        'numero',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function vehiculoTarjetas()
    {
        return $this->hasMany(VehiculoTarjeta::class);
    }

    public function vehiculoActivo()
    {
        return $this->hasOne(VehiculoTarjeta::class)
            ->where('activo', true)
            ->orderByDesc('fecha_inicio')   
            ->orderByDesc('id');
    }
}
