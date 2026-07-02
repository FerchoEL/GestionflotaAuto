<?php

namespace App\Filament\Resources\FondeoResource\Pages;

use App\Filament\Resources\FondeoResource;
use App\Services\TarjetaMovimientoService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditFondeo extends EditRecord
{
    protected static string $resource = FondeoResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $vehiculoId = $data['vehiculo_id'] ?? $this->record->vehiculo_id;

        $tarjetaCombustibleId = app(TarjetaMovimientoService::class)
            ->resolverTarjetaIdVehiculoEnFecha($vehiculoId, $data['fecha_fondeado'] ?? $this->record->fecha_fondeado);

        if (! $tarjetaCombustibleId) {
            throw ValidationException::withMessages([
                'data.fecha_fondeado' => 'El vehículo seleccionado no tiene una tarjeta asignada para la fecha del fondeo.',
            ]);
        }

        $data['vehiculo_id'] = $vehiculoId;
        $data['tarjeta_combustible_id'] = $tarjetaCombustibleId;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->refresh();

        app(TarjetaMovimientoService::class)->sincronizarFondeo($this->record);

        Notification::make()
            ->title('El fondeo se actualizó correctamente')
            ->body('También se sincronizó el saldo de la tarjeta One Card.')
            ->success()
            ->send();
    }
}
