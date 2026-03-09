<?php

namespace App\Filament\Resources\VehiculoLocalidadResource\Pages;

use App\Filament\Resources\VehiculoLocalidadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehiculoLocalidads extends ListRecords
{
    protected static string $resource = VehiculoLocalidadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
