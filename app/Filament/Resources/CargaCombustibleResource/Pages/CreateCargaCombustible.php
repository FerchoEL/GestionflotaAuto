<?php

namespace App\Filament\Resources\CargaCombustibleResource\Pages;

use App\Filament\Resources\CargaCombustibleResource;
use App\Models\Vehiculo;
use App\Models\CargaCombustible;
use App\Services\RendimientoService;
use App\Services\TarjetaMovimientoService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateCargaCombustible extends CreateRecord
{
    protected static string $resource = CargaCombustibleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        if (CargaCombustibleResource::esChoferEstricto()) {
            $data['fecha_carga'] = now()->format('Y-m-d H:i:s');
        }

        $vehiculo = Vehiculo::find($data['vehiculo_id']);

        if (! $vehiculo) {

            Notification::make()
                ->title('Vehículo no válido')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        if (! $vehiculo->responsables()->where('activo', true)->exists()) {

            Notification::make()
                ->title('No se puede registrar la carga')
                ->body('El vehículo no tiene un responsable activo asignado.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        $tarjetaCombustibleId = app(TarjetaMovimientoService::class)
            ->resolverTarjetaIdVehiculoEnFecha($data['vehiculo_id'] ?? null, $data['fecha_carga'] ?? null);

        if (! $tarjetaCombustibleId) {
            throw ValidationException::withMessages([
                'data.vehiculo_id' => 'El vehículo seleccionado no tiene una tarjeta asignada para la fecha de la carga.',
            ]);
        }

        $data['tarjeta_combustible_id'] = $tarjetaCombustibleId;

        return $data;
    }

    protected function handleRecordCreation(array $data): CargaCombustible
    {
        try {
            return CargaCombustible::create($data);

        } catch (Throwable $e) {

            Log::error('Error al crear CargaCombustible', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            Notification::make()
                ->title('No se pudo guardar la carga')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        try {
            $servicio = app(RendimientoService::class);

            $tieneCargasPosteriores = CargaCombustible::query()
                ->where('vehiculo_id', $this->record->vehiculo_id)
                ->where(function ($query) {
                    $query->where('fecha_carga', '>', $this->record->fecha_carga)
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('fecha_carga', $this->record->fecha_carga)
                                ->where('id', '>', $this->record->id);
                        });
                })
                ->exists();

            if ($tieneCargasPosteriores) {
                $servicio->recalcularDesdeCarga($this->record);

                Notification::make()
                    ->title('La carga se registró correctamente')
                    ->body('Esta acción recalculó rendimientos y alertas posteriores.')
                    ->success()
                    ->send();

                return;
            }

            $servicio->procesarCarga($this->record);

        } catch (Throwable $e) {

            Log::error('Error en cálculo de rendimiento', [
                'error' => $e->getMessage(),
                'carga_id' => $this->record->id,
            ]);

            Notification::make()
                ->title('La carga se guardó pero falló el cálculo de rendimiento')
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
