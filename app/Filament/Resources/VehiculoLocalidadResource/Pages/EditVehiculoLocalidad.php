<?php

namespace App\Filament\Resources\VehiculoLocalidadResource\Pages;

use App\Filament\Resources\VehiculoLocalidadResource;
use App\Services\VehiculoAsignacionActivaService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVehiculoLocalidad extends EditRecord
{
    protected static string $resource = VehiculoLocalidadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(VehiculoAsignacionActivaService::class)->guardarLocalidad([
            ...$data,
            'asignado_por_user_id' => $data['asignado_por_user_id'] ?? $record->asignado_por_user_id ?? auth()->id(),
        ], $record);
    }
}
