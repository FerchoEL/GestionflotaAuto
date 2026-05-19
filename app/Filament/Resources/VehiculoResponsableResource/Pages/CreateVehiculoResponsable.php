<?php

namespace App\Filament\Resources\VehiculoResponsableResource\Pages;

use App\Filament\Resources\VehiculoResponsableResource;
use App\Services\VehiculoAsignacionActivaService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVehiculoResponsable extends CreateRecord
{
    protected static string $resource = VehiculoResponsableResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(VehiculoAsignacionActivaService::class)->guardarResponsable($data);
    }
}
