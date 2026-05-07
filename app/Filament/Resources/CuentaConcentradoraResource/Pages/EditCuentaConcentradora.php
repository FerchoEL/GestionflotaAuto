<?php

namespace App\Filament\Resources\CuentaConcentradoraResource\Pages;

use App\Filament\Resources\CuentaConcentradoraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCuentaConcentradora extends EditRecord
{
    protected static string $resource = CuentaConcentradoraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
