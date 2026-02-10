<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoChofer extends Model
{
    protected $table = 'vehiculo_choferes';

    protected $fillable = [
        'vehiculo_id',
        'chofer_user_id',
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

    public function chofer()
    {
        return $this->belongsTo(User::class, 'chofer_user_id', 'id');
    }
    protected static function booted()
{
    static::creating(function ($registro) {
        static::where('vehiculo_id', $registro->vehiculo_id)
            ->where('chofer_user_id', $registro->chofer_user_id)
            ->where('activo', true)
            ->update([
                'activo' => false,
                'fecha_fin' => now(),
            ]);
    });
}
}