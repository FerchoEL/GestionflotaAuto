<?php

namespace App\Filament\Resources\VehiculoDepartamentoResource\Pages;

use App\Filament\Resources\VehiculoDepartamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehiculoDepartamento extends EditRecord
{
    protected static string $resource = VehiculoDepartamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
