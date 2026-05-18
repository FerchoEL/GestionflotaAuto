<?php

namespace App\Filament\Resources\FondeoResource\Pages;

use App\Filament\Resources\FondeoResource;
use App\Services\TarjetaMovimientoService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditFondeo extends EditRecord
{
    protected static string $resource = FondeoResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tarjetaCombustibleId = app(TarjetaMovimientoService::class)
            ->resolverTarjetaIdVehiculoEnFecha($data['vehiculo_id'] ?? null, $data['fecha_fondeado'] ?? null);

        if (! $tarjetaCombustibleId) {
            throw ValidationException::withMessages([
                'data.vehiculo_id' => 'El vehículo seleccionado no tiene una tarjeta asignada para la fecha del fondeo.',
            ]);
        }

        $data['tarjeta_combustible_id'] = $tarjetaCombustibleId;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
