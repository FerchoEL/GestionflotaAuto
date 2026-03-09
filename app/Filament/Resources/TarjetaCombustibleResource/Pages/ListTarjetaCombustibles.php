<?php

namespace App\Filament\Resources\TarjetaCombustibleResource\Pages;

use App\Filament\Resources\TarjetaCombustibleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTarjetaCombustibles extends ListRecords
{
    protected static string $resource = TarjetaCombustibleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
