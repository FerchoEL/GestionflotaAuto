<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaFondeo extends Model
{
    protected $fillable = [
        'vehiculo_id',
        'fondeo_id',
        'tipo',
        'descripcion',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function fondeo()
    {
        return $this->belongsTo(Fondeo::class);
    }
}