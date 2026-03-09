<?php

namespace App\Filament\Resources\VehiculoResource\Pages;

use App\Filament\Resources\VehiculoResource;
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

    // RESPONSABLE
    $actualResponsable = optional($vehiculo->responsableActivo)->responsable_user_id;

    if ($this->data['responsable_user_id'] != $actualResponsable) {
        \App\Models\VehiculoResponsable::create([
            'vehiculo_id' => $vehiculo->id,
            'responsable_user_id' => $this->data['responsable_user_id'],
            'fecha_inicio' => now(),
            'activo' => true,
        ]);
    }

    // DEPARTAMENTO
    $actualDepartamento = optional($vehiculo->departamentoActivo)->departamento_id;

    if ($this->data['departamento_id'] != $actualDepartamento) {
        \App\Models\VehiculoDepartamento::create([
            'vehiculo_id' => $vehiculo->id,
            'departamento_id' => $this->data['departamento_id'],
            'fecha_inicio' => now(),
            'activo' => true,
            'asignado_por_user_id' => auth()->id(),
        ]);
    }

    // LOCALIDAD
    $actualLocalidad = optional($vehiculo->localidadActiva)->localidad_id;

    if ($this->data['localidad_id'] != $actualLocalidad) {
        \App\Models\VehiculoLocalidad::create([
            'vehiculo_id' => $vehiculo->id,
            'localidad_id' => $this->data['localidad_id'],
            'fecha_inicio' => now(),
            'activo' => true,
            'asignado_por_user_id' => auth()->id(),
        ]);
    }
}
}
