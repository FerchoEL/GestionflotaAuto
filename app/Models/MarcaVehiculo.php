<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarcaVehiculo extends Model
{
    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $marcaVehiculo): void {
            if (! $marcaVehiculo->wasChanged('nombre')) {
                return;
            }

            DB::table('vehiculos')
                ->where('marca_vehiculo_id', $marcaVehiculo->id)
                ->update([
                    'marca' => $marcaVehiculo->nombre,
                    'updated_at' => now(),
                ]);
        });
    }

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'marca_vehiculo_id', 'id');
    }
}
