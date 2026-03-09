<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fondeo extends Model
{
    protected $fillable = [
        'vehiculo_id',
        'litros_fondeados',
        'importe_fondeado',
        'fecha_fondeado',
        'fondeado_por_user_id',
        'comentario',
    ];

    protected $casts = [
        'fecha_fondeado' => 'datetime',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function fondeadoPor()
    {
        return $this->belongsTo(User::class, 'fondeado_por_user_id');
    }
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
