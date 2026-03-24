<?php

namespace App\Filament\Resources\VehiculoDocumentoResource\Pages;

use App\Filament\Resources\VehiculoDocumentoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehiculoDocumentos extends ListRecords
{
    protected static string $resource = VehiculoDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
