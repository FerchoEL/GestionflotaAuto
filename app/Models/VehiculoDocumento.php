<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoDocumento extends Model
{
    protected $table = 'vehiculo_documentos';

    protected $fillable = [
        'vehiculo_id',
        'tipo_documento_id',
        'nombre',
        'archivo_path',
        'fecha_emision',
        'fecha_vencimiento',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function requiereVigencia(): bool
    {
        return (bool) $this->tipoDocumento?->requiere_vigencia;
    }

    public function colorEstadoVigencia(): string
    {
        if (! $this->requiereVigencia() || ! $this->fecha_vencimiento) {
            return 'gray';
        }

        if ($this->fecha_vencimiento->isPast()) {
            return 'danger';
        }

        $diasAlertaPrevia = (int) ($this->tipoDocumento?->dias_alerta_previa ?? 15);

        if (now()->diffInDays($this->fecha_vencimiento, false) <= $diasAlertaPrevia) {
            return 'warning';
        }

        return 'success';
    }

    public function estadoAlertaDocumento(): ?string
    {
        if (! $this->requiereVigencia() || ! $this->fecha_vencimiento) {
            return null;
        }

        if ($this->fecha_vencimiento->isPast()) {
            return 'vencido';
        }

        $diasAlertaPrevia = (int) ($this->tipoDocumento?->dias_alerta_previa ?? 15);

        if (now()->diffInDays($this->fecha_vencimiento, false) <= $diasAlertaPrevia) {
            return 'por_vencer';
        }

        return null;
    }
}
