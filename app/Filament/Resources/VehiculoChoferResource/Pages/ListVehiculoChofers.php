<?php

namespace App\Filament\Resources\VehiculoChoferResource\Pages;

use App\Filament\Resources\VehiculoChoferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehiculoChofers extends ListRecords
{
    protected static string $resource = VehiculoChoferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
