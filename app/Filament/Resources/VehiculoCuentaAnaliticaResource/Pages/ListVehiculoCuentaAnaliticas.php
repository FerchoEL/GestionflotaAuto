<?php

namespace App\Filament\Resources\VehiculoCuentaAnaliticaResource\Pages;

use App\Filament\Resources\VehiculoCuentaAnaliticaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehiculoCuentaAnaliticas extends ListRecords
{
    protected static string $resource = VehiculoCuentaAnaliticaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
