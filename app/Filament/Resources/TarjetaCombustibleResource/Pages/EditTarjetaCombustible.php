<?php

namespace App\Filament\Resources\TarjetaCombustibleResource\Pages;

use App\Filament\Resources\TarjetaCombustibleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTarjetaCombustible extends EditRecord
{
    protected static string $resource = TarjetaCombustibleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
