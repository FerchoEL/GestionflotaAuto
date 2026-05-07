<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarjetaSaldoMovimiento extends Model
{
    protected $fillable = [
        'tarjeta_combustible_id',
        'tipo',
        'monto',
        'fecha_movimiento',
        'tarjeta_destino_id',
        'cuenta_concentradora_id',
        'registrado_por_user_id',
        'referencia',
        'comentario',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_movimiento' => 'datetime',
    ];

    public function tarjeta()
    {
        return $this->belongsTo(TarjetaCombustible::class, 'tarjeta_combustible_id');
    }

    public function tarjetaDestino()
    {
        return $this->belongsTo(TarjetaCombustible::class, 'tarjeta_destino_id');
    }

    public function cuentaConcentradora()
    {
        return $this->belongsTo(CuentaConcentradora::class, 'cuenta_concentradora_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }
}
