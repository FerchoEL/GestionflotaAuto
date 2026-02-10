<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargaCombustible extends Model
{
    protected $table = 'carga_combustibles';

    protected $fillable = [
        'vehiculo_id',
        'user_id',
        'fecha_carga',
        'km_odometro',
        'litros',
        'importe',
        'foto_odometro_path',
        'foto_ticket_path',
    ];

    protected $casts = [
        'fecha_carga' => 'datetime',
        'litros' => 'decimal:2',
        'importe' => 'decimal:2',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function rendimiento()
    {
        return $this->hasOne(Rendimiento::class, 'carga_id', 'id');
    }
}

