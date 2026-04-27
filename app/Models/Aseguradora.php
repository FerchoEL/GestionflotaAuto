<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aseguradora extends Model
{
    protected $table = 'aseguradoras';

    protected $fillable = [
        'nombre',
        'numero_telefonico',
        'email',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function polizas(): HasMany
    {
        return $this->hasMany(PolizaSeguro::class, 'aseguradora_id');
    }
}
