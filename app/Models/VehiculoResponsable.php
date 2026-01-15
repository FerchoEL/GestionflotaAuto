<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoResponsable extends Model
{
    protected $table = 'vehiculo_responsables';

    protected $fillable = [
        'vehiculo_id',
        'responsable_user_id',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id', 'id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_user_id', 'id');
    }
}
