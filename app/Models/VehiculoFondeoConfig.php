<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoFondeoConfig extends Model
{
    protected $table = 'vehiculo_fondeo_configs';

    protected $fillable = [
        'vehiculo_id',
        'litros_autorizados_semanales',
        'activo',
        'fecha_inicio',
        'fecha_fin',
        'comentario',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id', 'id');
    }
}