<?php

namespace App\Filament\Resources\VehiculoTarjetaResource\Pages;

use App\Filament\Resources\VehiculoTarjetaResource;
use App\Services\VehiculoAsignacionActivaService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVehiculoTarjeta extends CreateRecord
{
    protected static string $resource = VehiculoTarjetaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(VehiculoAsignacionActivaService::class)->guardarTarjeta($data);
    }
}
