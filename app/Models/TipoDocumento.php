<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoDocumento extends Model
{
    protected $table = 'tipos_documento';

    protected $fillable = [
        'nombre',
        'requiere_vigencia',
        'dias_alerta_previa',
        'es_obligatorio',
    ];

    protected function casts(): array
    {
        return [
            'requiere_vigencia' => 'boolean',
            'es_obligatorio' => 'boolean',
        ];
    }

    public function vehiculoDocumentos(): HasMany
    {
        return $this->hasMany(VehiculoDocumento::class, 'tipo_documento_id');
    }
}
