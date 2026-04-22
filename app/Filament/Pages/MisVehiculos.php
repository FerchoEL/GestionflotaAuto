<?php

namespace App\Filament\Pages;

use App\Models\CargaCombustible;
use App\Models\Vehiculo;
use App\Models\AlertaRendimiento;
use App\Models\VehiculoDocumento;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\WithPagination;

class MisVehiculos extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string $view = 'filament.pages.mis-vehiculos';
    protected static ?string $navigationGroup = 'Operación';
    protected static ?string $navigationLabel = 'Mis Vehículos';

    public ?int $vehiculoId = null;
    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $vehiculos = $this->vehiculosAsignados();

        if ($vehiculos->isNotEmpty()) {
            $this->vehiculoId = $vehiculos->first()->id;
        }
    }

    public function updatedVehiculoId(): void
    {
        $this->resetPage('historialPage');
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
                'documentos.tipoDocumento',
            ])
            ->find($this->vehiculoId);
    }

    public function historialRendimiento(): LengthAwarePaginator
    {
        if (!$this->vehiculoId) {
            return new LengthAwarePaginator([], 0, 10, 1, [
                'path' => request()->url(),
                'pageName' => 'historialPage',
            ]);
        }

        $historial = CargaCombustible::query()
            ->with(['rendimiento'])
            ->where('vehiculo_id', $this->vehiculoId)
            ->orderByDesc('fecha_carga')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'historialPage');

        $historial->setCollection(
            $historial->getCollection()->map(function (CargaCombustible $carga) {
                $rendimiento = $carga->rendimiento;

                return (object) [
                    'fecha' => $carga->fecha_carga?->format('d/m/Y h:i A') ?? '—',
                    'km_actuales' => $carga->km_odometro !== null ? number_format((float) $carga->km_odometro, 0) : '—',
                    'km_recorridos' => $rendimiento?->km_recorridos !== null ? number_format((float) $rendimiento->km_recorridos, 0) : '—',
                    'litros' => $carga->litros !== null ? number_format((float) $carga->litros, 2) : '—',
                    'rendimiento_km_l' => $rendimiento?->rendimiento_km_l !== null ? number_format((float) $rendimiento->rendimiento_km_l, 2) . ' km/L' : '—',
                    'precio_litro' => $carga->precio_litro !== null ? '$' . number_format((float) $carga->precio_litro, 2) : '—',
                    'importe' => $carga->importe !== null ? '$' . number_format((float) $carga->importe, 2) : '—',
                ];
            })
        );

        return $historial;
    }

    public function documentosVehiculo(): Collection
    {
        if (! $this->vehiculoId) {
            return collect();
        }

        return VehiculoDocumento::query()
            ->with('tipoDocumento')
            ->where('vehiculo_id', $this->vehiculoId)
            ->orderBy('tipo_documento_id')
            ->orderBy('nombre')
            ->get()
            ->map(function (VehiculoDocumento $documento) {
                return (object) [
                    'nombre_documento' => $documento->tipoDocumento?->nombre ?? 'Sin tipo',
                    'nombre_archivo' => $documento->nombre,
                    'fecha_vigencia' => $documento->fecha_vencimiento?->format('d/m/Y') ?? 'Sin vigencia',
                    'estado_vigencia' => $documento->colorEstadoVigencia(),
                    'url_descarga' => Storage::disk('public')->url($documento->archivo_path),
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
