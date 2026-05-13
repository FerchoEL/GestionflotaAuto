<?php

namespace App\Filament\Resources\MarcaVehiculoResource\Pages;

use App\Filament\Resources\MarcaVehiculoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarcaVehiculo extends EditRecord
{
    protected static string $resource = MarcaVehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
