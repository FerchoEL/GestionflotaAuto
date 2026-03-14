<?php

namespace App\Filament\Resources\VehiculoCuentaAnaliticaResource\Pages;

use App\Filament\Resources\VehiculoCuentaAnaliticaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehiculoCuentaAnalitica extends EditRecord
{
    protected static string $resource = VehiculoCuentaAnaliticaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
