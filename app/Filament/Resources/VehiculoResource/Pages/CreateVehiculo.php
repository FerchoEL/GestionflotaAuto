<?php

namespace App\Filament\Resources\VehiculoResource\Pages;

use App\Filament\Resources\VehiculoResource;
use Filament\Actions;
use App\Models\VehiculoResponsable;
use App\Models\VehiculoDepartamento;
use App\Models\VehiculoLocalidad;
use Filament\Resources\Pages\CreateRecord;

class CreateVehiculo extends CreateRecord
{
    protected static string $resource = VehiculoResource::class;

    protected function afterCreate(): void
    {
        VehiculoResponsable::create([
            'vehiculo_id' => $this->record->id,
            'responsable_user_id' => $this->data['responsable_user_id'],
            'fecha_inicio' => now(),
            'activo' => true,
        ]);

        VehiculoDepartamento::create([
        'vehiculo_id' => $this->record->id,
        'departamento_id' => $this->data['departamento_id'],
        'fecha_inicio' => now(),
        'activo' => true,
        'asignado_por_user_id' => auth()->id(),
    ]);

    VehiculoLocalidad::create([
        'vehiculo_id' => $this->record->id,
        'localidad_id' => $this->data['localidad_id'],
        'fecha_inicio' => now(),
        'activo' => true,
        'asignado_por_user_id' => auth()->id(),
    ]);
    }
}
