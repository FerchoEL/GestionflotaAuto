<?php

namespace App\Filament\Resources\VehiculoResource\Pages;

use App\Filament\Resources\VehiculoResource;
use App\Services\VehiculoAsignacionActivaService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehiculo extends EditRecord
{
    protected static string $resource = VehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function afterSave(): void
    {
        $vehiculo = $this->record;
        $service = app(VehiculoAsignacionActivaService::class);

        $actualResponsable = optional($vehiculo->responsableActivo)->responsable_user_id;

        if ($this->data['responsable_user_id'] != $actualResponsable) {
            $service->guardarResponsable([
                'vehiculo_id' => $vehiculo->id,
                'responsable_user_id' => $this->data['responsable_user_id'],
                'fecha_inicio' => now(),
                'activo' => true,
            ]);
        }

        $actualDepartamento = optional($vehiculo->departamentoActivo)->departamento_id;

        if ($this->data['departamento_id'] != $actualDepartamento) {
            $service->guardarDepartamento([
                'vehiculo_id' => $vehiculo->id,
                'departamento_id' => $this->data['departamento_id'],
                'fecha_inicio' => now(),
                'activo' => true,
                'asignado_por_user_id' => auth()->id(),
            ]);
        }

        $actualLocalidad = optional($vehiculo->localidadActiva)->localidad_id;

        if ($this->data['localidad_id'] != $actualLocalidad) {
            $service->guardarLocalidad([
                'vehiculo_id' => $vehiculo->id,
                'localidad_id' => $this->data['localidad_id'],
                'fecha_inicio' => now(),
                'activo' => true,
                'asignado_por_user_id' => auth()->id(),
            ]);
        }
    }
}
