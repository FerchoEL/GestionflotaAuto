<?php

namespace App\Filament\Resources\VehiculoResource\Pages;

use App\Filament\Resources\VehiculoResource;
use Filament\Actions;
use App\Models\VehiculoResponsable;
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
    }
}
