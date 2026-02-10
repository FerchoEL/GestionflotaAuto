<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoEstatus extends Model
{
    protected $table = 'vehiculo_estatus';

    protected $fillable = [
        'nombre',
        'activo',
    ];
    protected $casts = [
        'activo' => 'boolean',
    ];

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'estatus_id');
    }
}
