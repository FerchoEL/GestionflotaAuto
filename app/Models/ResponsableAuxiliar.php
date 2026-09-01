<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponsableAuxiliar extends Model
{
    protected $table = 'responsable_auxiliares';

    protected $fillable = [
        'responsable_user_id',
        'auxiliar_user_id',
        'activo',
        'asignado_por_user_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_user_id', 'id');
    }

    public function auxiliar()
    {
        return $this->belongsTo(User::class, 'auxiliar_user_id', 'id');
    }

    public function asignadoPor()
    {
        return $this->belongsTo(User::class, 'asignado_por_user_id', 'id');
    }
}
