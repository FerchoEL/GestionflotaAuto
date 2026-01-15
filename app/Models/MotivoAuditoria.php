<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivoAuditoria extends Model
{
    protected $table = 'motivo_auditorias';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    public function alertas()
    {
        return $this->hasMany(AlertaRendimiento::class);
    }
}
