<?php

namespace App\Filament\Resources\VehiculoDocumentoResource\Pages;

use App\Filament\Resources\VehiculoDocumentoResource;
use App\Models\PolizaSeguro;
use App\Models\TipoDocumento;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVehiculoDocumento extends CreateRecord
{
    protected static string $resource = VehiculoDocumentoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);

        // Si es una póliza de seguro, crear el registro asociado
        if ($this->esPolizaSeguro($record->tipo_documento_id)) {
            PolizaSeguro::create([
                'vehiculo_documento_id' => $record->id,
                'aseguradora_id' => $data['poliza_aseguradora_id'] ?? null,
                'costo_poliza' => $data['poliza_costo'] ?? 0,
                'tipo_pago_id' => $data['poliza_tipo_pago_id'] ?? null,
                'notas' => $data['poliza_notas'] ?? null,
            ]);
        }

        return $record;
    }

    private function esPolizaSeguro(?int $tipoDocumentoId): bool
    {
        if (!$tipoDocumentoId) {
            return false;
        }

        return (bool) TipoDocumento::query()
            ->whereKey($tipoDocumentoId)
            ->value('es_poliza_seguro');
    }
}

