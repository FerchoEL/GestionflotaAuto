<?php

namespace App\Filament\Resources\MarcaVehiculoResource\Pages;

use App\Filament\Resources\MarcaVehiculoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarcaVehiculos extends ListRecords
{
    protected static string $resource = MarcaVehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
