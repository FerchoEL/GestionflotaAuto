<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table = 'departamentos';
    protected $fillable = [
        'nombre',
        'codigo',
        'activo',
    ];

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class);
    }
}
