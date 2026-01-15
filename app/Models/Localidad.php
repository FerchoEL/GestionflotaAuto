<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Localidad extends Model
{
    protected $table = 'localidades';
    protected $fillable = [
        'nombre',
        'ciudad',
        'estado',
        'activo',
    ];

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class);
    }
}
