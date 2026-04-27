<?php

namespace App\Filament\Resources\PolizaSeguroResource\Pages;

use App\Filament\Resources\PolizaSeguroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPolizasSeguro extends ListRecords
{
    protected static string $resource = PolizaSeguroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
