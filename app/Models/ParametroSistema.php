<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametroSistema extends Model
{
    protected $table = 'parametro_sistemas';

    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
        'activo',
    ];
}
