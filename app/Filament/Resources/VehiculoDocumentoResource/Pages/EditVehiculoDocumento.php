<?php

namespace App\Filament\Resources\VehiculoDocumentoResource\Pages;

use App\Filament\Resources\VehiculoDocumentoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehiculoDocumento extends EditRecord
{
    protected static string $resource = VehiculoDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
