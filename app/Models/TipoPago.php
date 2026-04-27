<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoPago extends Model
{
    protected $table = 'tipo_pagos';

    protected $fillable = [
        'nombre',
        'periodicidad_dias',
    ];

    public function polizas(): HasMany
    {
        return $this->hasMany(PolizaSeguro::class, 'tipo_pago_id');
    }
}
