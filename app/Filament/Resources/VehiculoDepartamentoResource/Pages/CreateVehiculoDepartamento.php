<?php

namespace App\Filament\Resources\VehiculoDepartamentoResource\Pages;

use App\Filament\Resources\VehiculoDepartamentoResource;
use App\Models\VehiculoDepartamento;
use App\Services\VehiculoAsignacionActivaService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVehiculoDepartamento extends CreateRecord
{
    protected static string $resource = VehiculoDepartamentoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(VehiculoAsignacionActivaService::class)->guardarDepartamento([
            ...$data,
            'asignado_por_user_id' => $data['asignado_por_user_id'] ?? auth()->id(),
        ]);
    }
}
