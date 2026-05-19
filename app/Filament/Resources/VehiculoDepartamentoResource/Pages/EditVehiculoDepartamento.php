<?php

namespace App\Filament\Resources\VehiculoDepartamentoResource\Pages;

use App\Filament\Resources\VehiculoDepartamentoResource;
use App\Services\VehiculoAsignacionActivaService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVehiculoDepartamento extends EditRecord
{
    protected static string $resource = VehiculoDepartamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(VehiculoAsignacionActivaService::class)->guardarDepartamento([
            ...$data,
            'asignado_por_user_id' => $data['asignado_por_user_id'] ?? $record->asignado_por_user_id ?? auth()->id(),
        ], $record);
    }
}
