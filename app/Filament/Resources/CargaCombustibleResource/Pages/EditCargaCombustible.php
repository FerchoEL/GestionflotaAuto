<?php

namespace App\Filament\Resources\CargaCombustibleResource\Pages;

use App\Filament\Resources\CargaCombustibleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCargaCombustible extends EditRecord
{
    protected static string $resource = CargaCombustibleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
