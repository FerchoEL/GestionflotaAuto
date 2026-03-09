<?php

namespace App\Filament\Resources\VehiculoDepartamentoResource\Pages;

use App\Filament\Resources\VehiculoDepartamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehiculoDepartamentos extends ListRecords
{
    protected static string $resource = VehiculoDepartamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
