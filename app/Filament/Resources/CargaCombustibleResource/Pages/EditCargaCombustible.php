<?php

namespace App\Filament\Resources\CargaCombustibleResource\Pages;

use App\Filament\Resources\CargaCombustibleResource;
use App\Models\AlertaRendimiento;
use App\Models\CargaCombustible;
use App\Models\Rendimiento;
use App\Services\RendimientoService;
use App\Services\TarjetaMovimientoService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditCargaCombustible extends EditRecord
{
    protected static string $resource = CargaCombustibleResource::class;

    protected ?CargaCombustible $siguienteCargaParaRecalculo = null;

    protected ?int $vehiculoOriginalId = null;

    protected ?string $fechaCargaOriginal = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->vehiculoOriginalId = $this->record->vehiculo_id;
        $this->fechaCargaOriginal = $this->record->getRawOriginal('fecha_carga');

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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (): void {
                    $this->siguienteCargaParaRecalculo = CargaCombustible::query()
                        ->where('vehiculo_id', $this->record->vehiculo_id)
                        ->where(function ($query) {
                            $query->where('fecha_carga', '>', $this->record->fecha_carga)
                                ->orWhere(function ($subQuery) {
                                    $subQuery->where('fecha_carga', $this->record->fecha_carga)
                                        ->where('id', '>', $this->record->id);
                                });
                        })
                        ->orderedChronologically()
                        ->first();

                    AlertaRendimiento::where('carga_id', $this->record->id)->delete();
                    Rendimiento::where('carga_id', $this->record->id)->delete();
                })
                ->after(function (): void {
                    if (! $this->siguienteCargaParaRecalculo) {
                        return;
                    }

                    app(RendimientoService::class)
                        ->recalcularDesdeCarga($this->siguienteCargaParaRecalculo);

                    Notification::make()
                        ->title('La carga se eliminó correctamente')
                        ->body('Esta acción recalculó rendimientos y alertas posteriores.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function afterSave(): void
    {
        try {
            $servicio = app(RendimientoService::class);

            $vehiculoActualizo = $this->vehiculoOriginalId !== null
                && (int) $this->vehiculoOriginalId !== (int) $this->record->vehiculo_id;

            $servicio->recalcularDesdeCarga($this->record);

            if ($vehiculoActualizo) {
                $cargaSiguienteVehiculoAnterior = $this->obtenerCargaSiguienteOriginal();

                if ($cargaSiguienteVehiculoAnterior) {
                    $servicio->recalcularDesdeCarga($cargaSiguienteVehiculoAnterior);
                }
            }

            Notification::make()
                ->title('La carga se actualizó correctamente')
                ->body('Esta acción recalculó rendimientos y alertas posteriores.')
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('La carga se guardó pero falló el recálculo')
                ->body($e->getMessage())
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function obtenerCargaSiguienteOriginal(): ?CargaCombustible
    {
        if ($this->vehiculoOriginalId === null || blank($this->fechaCargaOriginal)) {
            return null;
        }

        return CargaCombustible::query()
            ->where('vehiculo_id', $this->vehiculoOriginalId)
            ->where(function ($query) {
                $query->where('fecha_carga', '>', $this->fechaCargaOriginal)
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('fecha_carga', $this->fechaCargaOriginal)
                            ->where('id', '>', $this->record->id);
                    });
            })
            ->orderedChronologically()
            ->first();
    }
}
