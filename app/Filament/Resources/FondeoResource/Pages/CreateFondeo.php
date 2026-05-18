<?php

namespace App\Filament\Resources\FondeoResource\Pages;

use App\Filament\Resources\FondeoResource;
use App\Services\TarjetaMovimientoService;
use App\Models\Vehiculo;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class CreateFondeo extends CreateRecord
{
    protected static string $resource = FondeoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vehiculo = Vehiculo::query()->find($data['vehiculo_id'] ?? null);

        $tarjetaCombustibleId = app(TarjetaMovimientoService::class)
            ->resolverTarjetaIdVehiculoEnFecha($data['vehiculo_id'] ?? null, $data['fecha_fondeado'] ?? null);

        if (! $vehiculo || ! $tarjetaCombustibleId) {
            throw ValidationException::withMessages([
                'data.vehiculo_id' => 'El vehículo seleccionado no tiene una tarjeta asignada para la fecha del fondeo.',
            ]);
        }

        $data['fondeado_por_user_id'] = Auth::id();
        $data['tarjeta_combustible_id'] = $tarjetaCombustibleId;

        return $data;
    }
}
