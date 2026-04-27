<?php

namespace App\Filament\Resources\VehiculoDocumentoResource\Pages;

use App\Filament\Resources\VehiculoDocumentoResource;
use App\Models\PolizaSeguro;
use App\Models\TipoDocumento;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVehiculoDocumento extends EditRecord
{
    protected static string $resource = VehiculoDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Si es una póliza, cargar datos de poliza_seguro
        if ($this->esPolizaSeguro($data['tipo_documento_id'] ?? null)) {
            $poliza = PolizaSeguro::where('vehiculo_documento_id', $this->record->id)->first();
            
            if ($poliza) {
                $data['poliza_aseguradora_id'] = $poliza->aseguradora_id;
                $data['poliza_costo'] = $poliza->costo_poliza;
                $data['poliza_tipo_pago_id'] = $poliza->tipo_pago_id;
                $data['poliza_notas'] = $poliza->notas;
            }
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        // Si es una póliza de seguro, crear o actualizar el registro asociado
        if ($this->esPolizaSeguro($record->tipo_documento_id)) {
            PolizaSeguro::updateOrCreate(
                ['vehiculo_documento_id' => $record->id],
                [
                    'aseguradora_id' => $data['poliza_aseguradora_id'] ?? null,
                    'costo_poliza' => $data['poliza_costo'] ?? 0,
                    'tipo_pago_id' => $data['poliza_tipo_pago_id'] ?? null,
                    'notas' => $data['poliza_notas'] ?? null,
                ]
            );
        } else {
            // Si deja de ser una póliza, eliminar el registro asociado
            PolizaSeguro::where('vehiculo_documento_id', $record->id)->delete();
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


