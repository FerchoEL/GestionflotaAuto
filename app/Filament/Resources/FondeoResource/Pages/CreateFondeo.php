<?php

namespace App\Filament\Resources\FondeoResource\Pages;

use App\Filament\Resources\FondeoResource;
use App\Services\TarjetaMovimientoService;
use App\Models\Vehiculo;
use App\Models\TarjetaCombustible;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class CreateFondeo extends CreateRecord
{
    protected static string $resource = FondeoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ahora el formulario registra fondeos por tarjeta. Validamos la tarjeta seleccionada
        $tarjeta = TarjetaCombustible::query()->find($data['tarjeta_combustible_id'] ?? null);

        if (! $tarjeta) {
            throw ValidationException::withMessages([
                'data.tarjeta_combustible_id' => 'Selecciona una tarjeta válida.',
            ]);
        }

        // Si la tarjeta tiene un vehículo activo para la fecha, lo asociamos al fondeo (opcional)
        $vehiculo = $tarjeta->vehiculoActivo?->vehiculo ?? null;

        if ($vehiculo) {
            $data['vehiculo_id'] = $vehiculo->id;
        }

        $data['fondeado_por_user_id'] = Auth::id();

        return $data;
    }
}
