<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoFondeoConfig extends Model
{
    protected $fillable = [
        'vehiculo_id',
        'litros_asignados',
        'activo',
        'asignado_por_user_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function asignadoPor()
    {
        return $this->belongsTo(User::class, 'asignado_por_user_id');
    }
}
