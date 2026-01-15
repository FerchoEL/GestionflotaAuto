<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rendimiento extends Model
{
    protected $fillable = [
        'carga_id',
        'vehiculo_id',
        'km_anterior',
        'km_recorridos',
        'rendimiento_km_l',
        'es_base',
        'evaluado',
    ];

    protected $casts = [
        'es_base' => 'boolean',
        'evaluado' => 'boolean',
    ];

    public function carga()
    {
        return $this->belongsTo(CargaCombustible::class, 'carga_id', 'id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id', 'id');
    }
}
