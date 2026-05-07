<?php

namespace App\Filament\Resources\CuentaConcentradoraResource\Pages;

use App\Filament\Resources\CuentaConcentradoraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCuentaConcentradoras extends ListRecords
{
    protected static string $resource = CuentaConcentradoraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
