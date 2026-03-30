<?php

namespace App\Filament\Pages;

use App\Models\Vehiculo;
use App\Models\Rendimiento;
use App\Models\AlertaRendimiento;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class MisVehiculos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string $view = 'filament.pages.mis-vehiculos';
    protected static ?string $navigationGroup = 'Operación';
    protected static ?string $navigationLabel = 'Mis Vehículos';

    public ?int $vehiculoId = null;

    public function mount(): void
    {
        $vehiculos = $this->vehiculosAsignados();

        if ($vehiculos->isNotEmpty()) {
            $this->vehiculoId = $vehiculos->first()->id;
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'responsable', 'chofer']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'responsable', 'chofer']) ?? false;
    }

    public function vehiculosAsignados(): Collection
    {
        $user = auth()->user();
        $userId = $user->id;

        if ($user->hasRole('admin')) {
            return Vehiculo::query()
                ->where('activo', true)
                ->orderBy('numero_economico')
                ->orderBy('placas')
                ->get();
        }

        if ($user->hasRole('chofer')) {
            return Vehiculo::query()
                ->where('activo', true)
                ->whereHas('choferes', function ($q) use ($userId) {
                    $q->where('chofer_user_id', $userId)
                        ->where('activo', true)
                        ->where(function ($sub) {
                            $sub->whereNull('fecha_fin')
                                ->orWhere('fecha_fin', '>=', now());
                        });
                })
                ->orderBy('numero_economico')
                ->orderBy('placas')
                ->get();
        }

        return Vehiculo::query()
            ->where('activo', true)
            ->whereHas('responsableActivo', function ($q) use ($userId) {
                $q->where('responsable_user_id', $userId);
            })
            ->orderBy('numero_economico')
            ->orderBy('placas')
            ->get();
    }

    public function vehiculoSeleccionado(): ?Vehiculo
    {
        if (!$this->vehiculoId) {
            return null;
        }

        return Vehiculo::query()
            ->with([
                'tipoVehiculo',
                'estatus',
                'departamentoActivo.departamento',
                'localidadActiva.localidad',
                'responsableActivo.responsable',
                'choferActivo.chofer',
            ])
            ->find($this->vehiculoId);
    }

    public function historialRendimiento(): Collection
    {
        if (!$this->vehiculoId) {
            return collect();
        }

        return Rendimiento::query()
            ->with(['carga'])
            ->where('vehiculo_id', $this->vehiculoId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($rendimiento) {
                $carga = $rendimiento->carga;

                return (object) [
                    'fecha' => $carga?->fecha_carga ?? $rendimiento->created_at,
                    'km_actuales' => $carga?->km_odometro ?? '—',
                    'km_recorridos' => $rendimiento->km_recorridos ?? '—',
                    'litros' => $carga?->litros ?? '—',
                    'rendimiento_km_l' => $rendimiento->rendimiento_km_l ?? '—',
                    'precio_litro' => $carga?->precio_litro ?? '—',
                    'importe' => $carga?->importe ?? '—',
                ];
            });
    }

    public function alertasAbiertas(): Collection
    {
        if (!$this->vehiculoId) {
            return collect();
        }

        return AlertaRendimiento::query()
            ->where('vehiculo_id', $this->vehiculoId)
            ->where('estatus', 'Abierta')
            ->orderByDesc('fecha_alerta')
            ->get();
    }
}
