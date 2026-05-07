<?php

namespace App\Filament\Resources\FondeoResource\Pages;

use App\Filament\Resources\FondeoResource;
use App\Models\Vehiculo;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class CreateFondeo extends CreateRecord
{
    protected static string $resource = FondeoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vehiculo = Vehiculo::query()
            ->with('tarjetaActiva')
            ->find($data['vehiculo_id'] ?? null);

        if (! $vehiculo?->tarjetaActiva) {
            throw ValidationException::withMessages([
                'data.vehiculo_id' => 'El vehículo seleccionado no tiene una tarjeta activa asignada.',
            ]);
        }

        $data['fondeado_por_user_id'] = Auth::id();

        return $data;
    }
}
