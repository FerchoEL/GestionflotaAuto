<?php

namespace App\Filament\Resources\VehiculoTarjetaResource\Pages;

use App\Filament\Resources\VehiculoTarjetaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVehiculoTarjeta extends CreateRecord
{
    protected static string $resource = VehiculoTarjetaResource::class;
    protected static string $resource = VehiculoResource::class;

    protected function afterCreate(): void
    {
        $tarjetaId = $this->form->getState()['tarjeta_combustible_id'] ?? null;

        if ($tarjetaId) {
            VehiculoTarjeta::create([
                'vehiculo_id' => $this->record->id,
                'tarjeta_combustible_id' => $tarjetaId,
                'fecha_inicio' => now()->toDateString(),
                'activo' => true,
            ]);
        }
    }
}
