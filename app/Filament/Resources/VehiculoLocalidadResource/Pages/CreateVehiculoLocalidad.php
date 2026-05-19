<?php

namespace App\Filament\Resources\VehiculoLocalidadResource\Pages;

use App\Filament\Resources\VehiculoLocalidadResource;
use App\Services\VehiculoAsignacionActivaService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVehiculoLocalidad extends CreateRecord
{
    protected static string $resource = VehiculoLocalidadResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(VehiculoAsignacionActivaService::class)->guardarLocalidad([
            ...$data,
            'asignado_por_user_id' => $data['asignado_por_user_id'] ?? auth()->id(),
        ]);
    }
}
