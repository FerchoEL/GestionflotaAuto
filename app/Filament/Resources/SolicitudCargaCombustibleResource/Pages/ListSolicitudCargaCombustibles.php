<?php

namespace App\Filament\Resources\SolicitudCargaCombustibleResource\Pages;

use App\Filament\Resources\SolicitudCargaCombustibleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolicitudCargaCombustibles extends ListRecords
{
    protected static string $resource = SolicitudCargaCombustibleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
