<?php

namespace App\Filament\Resources\VehiculoLocalidadResource\Pages;

use App\Filament\Resources\VehiculoLocalidadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehiculoLocalidad extends EditRecord
{
    protected static string $resource = VehiculoLocalidadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
