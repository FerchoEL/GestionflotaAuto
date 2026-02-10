<?php

namespace App\Filament\Resources\VehiculoEstatusResource\Pages;

use App\Filament\Resources\VehiculoEstatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehiculoEstatus extends EditRecord
{
    protected static string $resource = VehiculoEstatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
