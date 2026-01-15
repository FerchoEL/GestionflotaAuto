<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaAlerta extends Model
{
    protected $table = 'auditoria_alertas';

    protected $fillable = [
        'alerta_id',
        'usuario_id',
        'comentario',
    ];

    public function alerta()
    {
        return $this->belongsTo(AlertaRendimiento::class, 'alerta_id', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }
}
