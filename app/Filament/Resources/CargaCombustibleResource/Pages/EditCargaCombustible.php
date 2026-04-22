<?php

namespace App\Filament\Resources\CargaCombustibleResource\Pages;

use App\Filament\Resources\CargaCombustibleResource;
use App\Models\AlertaRendimiento;
use App\Models\CargaCombustible;
use App\Models\Rendimiento;
use App\Services\RendimientoService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditCargaCombustible extends EditRecord
{
    protected static string $resource = CargaCombustibleResource::class;

    protected ?CargaCombustible $siguienteCargaParaRecalculo = null;

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
            app(RendimientoService::class)
                ->recalcularDesdeCarga($this->record);

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
}
