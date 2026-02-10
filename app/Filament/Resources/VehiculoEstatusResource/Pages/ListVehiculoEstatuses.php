<?php

namespace App\Filament\Resources\VehiculoEstatusResource\Pages;

use App\Filament\Resources\VehiculoEstatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehiculoEstatuses extends ListRecords
{
    protected static string $resource = VehiculoEstatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
