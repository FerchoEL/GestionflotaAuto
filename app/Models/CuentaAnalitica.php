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

    
    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cargas()
    {
        return $this->hasMany(CargaCombustible::class, 'cuenta_analitica_id');
    }

    public function vehiculoAsignaciones()
    {
        return $this->hasMany(VehiculoCuentaAnalitica::class, 'cuenta_analitica_id');
    }

}
