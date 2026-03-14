<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoCuentaAnalitica extends Model
{
    protected $table = 'vehiculo_cuenta_analiticas';

    protected $fillable = [
        'vehiculo_id',
        'cuenta_analitica_id',
        'asignado_por_user_id',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($registro) {
            static::where('vehiculo_id', $registro->vehiculo_id)
                ->where('activo', true)
                ->update([
                    'activo' => false,
                    'fecha_fin' => now()->toDateString(),
                ]);
        });
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id', 'id');
    }

    public function cuentaAnalitica()
    {
        return $this->belongsTo(CuentaAnalitica::class, 'cuenta_analitica_id', 'id');
    }

    public function asignadoPor()
    {
        return $this->belongsTo(User::class, 'asignado_por_user_id', 'id');
    }
}
