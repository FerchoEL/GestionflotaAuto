<?php

namespace App\Filament\Resources\SolicitudCargaCombustibleResource\Pages;

use App\Filament\Resources\SolicitudCargaCombustibleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolicitudCargaCombustible extends EditRecord
{
    protected static string $resource = SolicitudCargaCombustibleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return [
            'cuenta_analitica_id' => $data['cuenta_analitica_id'] ?? null,
        ];
    }
}
