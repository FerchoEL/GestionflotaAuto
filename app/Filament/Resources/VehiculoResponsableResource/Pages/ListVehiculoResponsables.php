<?php

namespace App\Filament\Resources\VehiculoResponsableResource\Pages;

use App\Filament\Resources\VehiculoResponsableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehiculoResponsables extends ListRecords
{
    protected static string $resource = VehiculoResponsableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
