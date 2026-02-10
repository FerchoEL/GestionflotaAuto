<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentroCosto extends Model
{
    protected $table = 'centros_costo';

    protected $fillable = [
        'nombre',
        'codigo',
        'activo',
    ];

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'centro_costo_id');
    }
}
