<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolizaSeguro extends Model
{
    protected $table = 'polizas_seguro';

    protected $fillable = [
        'vehiculo_documento_id',
        'aseguradora_id',
        'costo_poliza',
        'tipo_pago_id',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'costo_poliza' => 'decimal:2',
        ];
    }

    public function vehiculoDocumento(): BelongsTo
    {
        return $this->belongsTo(VehiculoDocumento::class, 'vehiculo_documento_id');
    }

    public function aseguradora(): BelongsTo
    {
        return $this->belongsTo(Aseguradora::class, 'aseguradora_id');
    }

    public function tipoPago(): BelongsTo
    {
        return $this->belongsTo(TipoPago::class, 'tipo_pago_id');
    }
}
