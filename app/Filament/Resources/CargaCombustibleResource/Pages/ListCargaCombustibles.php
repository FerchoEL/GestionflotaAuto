<?php

namespace App\Filament\Resources\CargaCombustibleResource\Pages;

use App\Filament\Resources\CargaCombustibleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCargaCombustibles extends ListRecords
{
    protected static string $resource = CargaCombustibleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
