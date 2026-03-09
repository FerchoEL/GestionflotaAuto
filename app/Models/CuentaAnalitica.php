<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuentaAnalitica extends Model
{
    protected $table = 'cuenta_analiticas';

    protected $fillable = [
        'nombre',
        'codigo',
        'activo',
    ];

    public function cargas()
    {
        return $this->hasMany(CargaCombustible::class);
    }
}
