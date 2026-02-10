<?php

namespace App\Filament\Resources\VehiculoChoferResource\Pages;

use App\Filament\Resources\VehiculoChoferResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehiculoChofer extends EditRecord
{
    protected static string $resource = VehiculoChoferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
