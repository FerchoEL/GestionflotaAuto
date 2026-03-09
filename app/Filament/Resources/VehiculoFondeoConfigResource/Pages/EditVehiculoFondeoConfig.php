<?php

namespace App\Filament\Resources\VehiculoFondeoConfigResource\Pages;

use App\Filament\Resources\VehiculoFondeoConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehiculoFondeoConfig extends EditRecord
{
    protected static string $resource = VehiculoFondeoConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
