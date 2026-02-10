<?php

namespace App\Filament\Resources\CargaCombustibleResource\Pages;

use App\Filament\Resources\CargaCombustibleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCargaCombustible extends CreateRecord
{
    protected static string $resource = CargaCombustibleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }

    protected function afterCreate(): void
    {
        app(RendimientoService::class)->procesarCarga($this->record);
    }
}
