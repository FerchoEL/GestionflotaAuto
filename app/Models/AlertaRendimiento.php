<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaRendimiento extends Model
{
    protected $table = 'alerta_rendimientos';

    protected $fillable = [
        'vehiculo_id',
        'responsable_user_id',
        'carga_id',
        'rendimiento_detectado',
        'rendimiento_optimo',
        'umbral_aplicado',
        'estatus',
        'motivo_auditoria_id',
        'fecha_alerta',
        'fecha_cierre',
    ];

    protected $casts = [
        'fecha_alerta' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id', 'id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_user_id', 'id');
    }

    public function carga()
    {
        return $this->belongsTo(CargaCombustible::class, 'carga_id', 'id');
    }

    public function motivo()
    {
        return $this->belongsTo(MotivoAuditoria::class, 'motivo_auditoria_id', 'id');
    }

    public function auditorias()
    {
        return $this->hasMany(AuditoriaAlerta::class, 'alerta_id');
    }
}
