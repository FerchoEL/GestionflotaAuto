<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaDocumento extends Model
{
    protected $table = 'alerta_documentos';

    protected $fillable = [
        'vehiculo_documento_id',
        'vehiculo_id',
        'tipo_documento_id',
        'responsable_user_id',
        'tipo',
        'descripcion',
        'estatus',
        'fecha_alerta',
        'fecha_cierre',
        'comentario',
    ];

    protected $casts = [
        'fecha_alerta' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function documento()
    {
        return $this->belongsTo(VehiculoDocumento::class, 'vehiculo_documento_id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }
}
