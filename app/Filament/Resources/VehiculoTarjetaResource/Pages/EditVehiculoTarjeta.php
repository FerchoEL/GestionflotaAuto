<?php

namespace App\Filament\Resources\VehiculoTarjetaResource\Pages;

use App\Filament\Resources\VehiculoTarjetaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehiculoTarjeta extends EditRecord
{
    protected static string $resource = VehiculoTarjetaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
