<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fondeo extends Model
{
    protected $fillable = [
        'vehiculo_id',
        'semana_inicio',
        'semana_fin',
        'litros_consumidos',
        'litros_a_fondear',
        'estatus',
        'fondeado_por_user_id',
        'fecha_fondeado',
        'comentario',
    ];

    protected $casts = [
        'semana_inicio' => 'date',
        'semana_fin' => 'date',
        'fecha_fondeado' => 'datetime',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id', 'id');
    }

    public function fondeadoPor()
    {
        return $this->belongsTo(User::class, 'fondeado_por_user_id', 'id');
    }
}
