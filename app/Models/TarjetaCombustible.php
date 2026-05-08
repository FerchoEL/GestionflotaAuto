<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarjetaCombustible extends Model
{
    protected $fillable = [
        'numero',
        'descripcion',
        'empleado_one_card',
        'convenio_id_one_card',
        'convenio_one_card',
        'sucursal_one_card',
        'nombre_one_card',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function setNumeroAttribute($value): void
    {
        $this->attributes['numero'] = static::normalizarNumero($value);
    }

    public static function normalizarNumero(?string $numero): string
    {
        $numero = trim((string) $numero);
        $soloDigitos = preg_replace('/\D+/', '', $numero);

        return $soloDigitos !== '' ? $soloDigitos : $numero;
    }

    public function vehiculoTarjetas()
    {
        return $this->hasMany(VehiculoTarjeta::class);
    }

    public function vehiculoActivo()
    {
        return $this->hasOne(VehiculoTarjeta::class)
            ->where('activo', true)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id');
    }

    public function saldoMovimientos()
    {
        return $this->hasMany(TarjetaSaldoMovimiento::class, 'tarjeta_combustible_id');
    }
}
