<?php

namespace App\Filament\Resources\VehiculoFondeoConfigResource\Pages;

use App\Filament\Resources\VehiculoFondeoConfigResource;
use App\Models\VehiculoFondeoConfig;
use Filament\Resources\Pages\CreateRecord;

class CreateVehiculoFondeoConfig extends CreateRecord
{
    protected static string $resource = VehiculoFondeoConfigResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 🔥 Desactivar configuración anterior activa
        VehiculoFondeoConfig::where('vehiculo_id', $data['vehiculo_id'])
            ->where('activo', true)
            ->update(['activo' => false]);

        $data['asignado_por_user_id'] = auth()->id();

        return $data;
    }
}
