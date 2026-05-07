<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuentaConcentradora extends Model
{
    protected $fillable = [
        'nombre',
        'codigo',
        'institucion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function movimientos()
    {
        return $this->hasMany(TarjetaSaldoMovimiento::class, 'cuenta_concentradora_id');
    }
}
