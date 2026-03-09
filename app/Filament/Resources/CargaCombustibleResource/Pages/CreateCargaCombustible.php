<?php

namespace App\Filament\Resources\CargaCombustibleResource\Pages;

use App\Filament\Resources\CargaCombustibleResource;
use App\Models\Vehiculo;
use App\Models\CargaCombustible;
use App\Services\RendimientoService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Throwable;

class CreateCargaCombustible extends CreateRecord
{
    protected static string $resource = CargaCombustibleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

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
            app(RendimientoService::class)
                ->procesarCarga($this->record);

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
