<?php

namespace App\Filament\Resources\VehiculoTarjetaResource\Pages;

use App\Filament\Resources\VehiculoTarjetaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehiculoTarjetas extends ListRecords
{
    protected static string $resource = VehiculoTarjetaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
