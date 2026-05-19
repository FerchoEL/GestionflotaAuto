<?php

namespace App\Filament\Resources\VehiculoResponsableResource\Pages;

use App\Filament\Resources\VehiculoResponsableResource;
use App\Services\VehiculoAsignacionActivaService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVehiculoResponsable extends EditRecord
{
    protected static string $resource = VehiculoResponsableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(VehiculoAsignacionActivaService::class)->guardarResponsable($data, $record);
    }
}
