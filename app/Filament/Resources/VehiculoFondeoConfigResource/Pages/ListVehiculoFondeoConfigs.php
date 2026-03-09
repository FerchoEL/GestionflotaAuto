<?php

namespace App\Filament\Resources\VehiculoFondeoConfigResource\Pages;

use App\Filament\Resources\VehiculoFondeoConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehiculoFondeoConfigs extends ListRecords
{
    protected static string $resource = VehiculoFondeoConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
