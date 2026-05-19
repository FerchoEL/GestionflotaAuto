<?php

namespace App\Filament\Resources\VehiculoTarjetaResource\Pages;

use App\Filament\Resources\VehiculoTarjetaResource;
use App\Services\VehiculoAsignacionActivaService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVehiculoTarjeta extends EditRecord
{
    protected static string $resource = VehiculoTarjetaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(VehiculoAsignacionActivaService::class)->guardarTarjeta($data, $record);
    }
}
