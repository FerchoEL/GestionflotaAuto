<?php

namespace App\Livewire\Admin;

use App\Filament\Pages\FondeoDashboard;
use App\Filament\Pages\MisVehiculos;
use App\Filament\Pages\ReporteCombustible;
use App\Filament\Resources\AlertaRendimientoResource;
use App\Filament\Resources\CargaCombustibleResource;
use App\Filament\Resources\CentroCostoResource;
use App\Filament\Resources\DepartamentoResource;
use App\Filament\Resources\FondeoResource;
use App\Filament\Resources\LocalidadResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\SolicitudCargaCombustibleResource;
use App\Filament\Resources\TarjetaCombustibleResource;
use App\Filament\Resources\TipoDocumentoResource;
use App\Filament\Resources\TipoVehiculoResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\VehiculoChoferResource;
use App\Filament\Resources\VehiculoCuentaAnaliticaResource;
use App\Filament\Resources\VehiculoDepartamentoResource;
use App\Filament\Resources\VehiculoDocumentoResource;
use App\Filament\Resources\VehiculoEstatusResource;
use App\Filament\Resources\VehiculoFondeoConfigResource;
use App\Filament\Resources\VehiculoLocalidadResource;
use App\Filament\Resources\VehiculoResource;
use App\Filament\Resources\VehiculoResponsableResource;
use App\Filament\Resources\VehiculoTarjetaResource;
use App\Models\AlertaRendimiento;
use App\Models\CargaCombustible;
use App\Models\CuentaAnalitica;
use App\Models\Departamento;
use App\Models\Fondeo;
use App\Models\Localidad;
use App\Models\TarjetaCombustible;
use App\Models\TipoDocumento;
use App\Models\TipoVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoChofer;
use App\Models\VehiculoCuentaAnalitica;
use App\Models\VehiculoDepartamento;
use App\Models\VehiculoDocumento;
use App\Models\VehiculoEstatus;
use App\Models\VehiculoFondeoConfig;
use App\Models\VehiculoLocalidad;
use App\Models\VehiculoResponsable;
use App\Models\VehiculoTarjeta;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Sidebar extends Component
{
    public function render()
    {
        return view('livewire.admin.sidebar', [
            'modules' => $this->modules(),
        ]);
    }

    protected function modules(): array
    {
        return [
            $this->buildModule(
                id: 'modulo-combustible',
                label: 'MÓDULO 1',
                title: 'Combustible',
                icon: 'heroicon-o-credit-card',
                sections: [
                    $this->buildSection('Flota', 'heroicon-o-truck', [
                        $this->resourceItem('Vehículos', 'heroicon-o-truck', VehiculoResource::class, Vehiculo::class),
                    ]),
                    $this->buildSection('Operación', 'heroicon-o-bolt', [
                        $this->pageItem('Mis Vehículos', 'heroicon-o-clipboard-document-list', MisVehiculos::class, Vehiculo::class),
                        $this->pageItem('Fondeo Operativo', 'heroicon-o-banknotes', FondeoDashboard::class, Fondeo::class),
                        $this->resourceItem('Carga de combustible', 'heroicon-o-beaker', CargaCombustibleResource::class, CargaCombustible::class),
                        $this->resourceItem('Tarjetas de combustible', 'heroicon-o-credit-card', TarjetaCombustibleResource::class, TarjetaCombustible::class),
                        $this->resourceItem(
                            'Alertas de rendimiento',
                            'heroicon-o-exclamation-triangle',
                            AlertaRendimientoResource::class,
                            AlertaRendimiento::class,
                            $this->openAlertsBadge(),
                        ),
                        $this->resourceItem(
                            'Solicitudes de Combustible',
                            'heroicon-o-clipboard-document-list',
                            SolicitudCargaCombustibleResource::class,
                            CargaCombustible::class,
                        ),
                    ]),
                    $this->buildSection('Reportes', 'heroicon-o-chart-bar', [
                        $this->pageItem('Reporte Combustible', 'heroicon-o-presentation-chart-line', ReporteCombustible::class, CargaCombustible::class),
                    ]),
                    $this->buildSection('Catálogos', 'heroicon-o-squares-2x2', [
                        $this->resourceItem('Cuentas Analíticas', 'heroicon-o-currency-dollar', CentroCostoResource::class, CuentaAnalitica::class),
                        $this->resourceItem('Departamentos', 'heroicon-o-building-office-2', DepartamentoResource::class, Departamento::class),
                        $this->resourceItem('Localidades', 'heroicon-o-map-pin', LocalidadResource::class, Localidad::class),
                        $this->resourceItem('Tipos de vehículo', 'heroicon-o-tag', TipoVehiculoResource::class, TipoVehiculo::class),
                        $this->resourceItem('Estatus de vehículos', 'heroicon-o-archive-box', VehiculoEstatusResource::class, VehiculoEstatus::class),
                        $this->resourceItem('Roles', 'heroicon-o-shield-check', RoleResource::class, Role::class),
                    ]),
                    $this->buildSection('Configuración', 'heroicon-o-cog-6-tooth', [
                        $this->resourceItem('Usuarios', 'heroicon-o-users', UserResource::class, User::class),
                        $this->resourceItem('Asig. Litros de Fondeo', 'heroicon-o-banknotes', VehiculoFondeoConfigResource::class, VehiculoFondeoConfig::class),
                        $this->resourceItem('Asig. Tarjeta a Vehículo', 'heroicon-o-credit-card', VehiculoTarjetaResource::class, VehiculoTarjeta::class),
                        $this->resourceItem('Asig. Responsables a Vehículo', 'heroicon-o-shield-check', VehiculoResponsableResource::class, VehiculoResponsable::class),
                        $this->resourceItem('Asig. Vehículo a operador', 'heroicon-o-user', VehiculoChoferResource::class, VehiculoChofer::class),
                        $this->resourceItem('Asig. Vehículo a Localidad', 'heroicon-o-map', VehiculoLocalidadResource::class, VehiculoLocalidad::class),
                        $this->resourceItem('Asig. Vehículo a Departamento', 'heroicon-o-building-office', VehiculoDepartamentoResource::class, VehiculoDepartamento::class),
                        $this->resourceItem('Asig. Cuenta Analítica', 'heroicon-o-calculator', VehiculoCuentaAnaliticaResource::class, VehiculoCuentaAnalitica::class),
                        $this->resourceItem('Fondeo manual', 'heroicon-o-wallet', FondeoResource::class, Fondeo::class),
                    ]),
                ],
            ),
            $this->buildModule(
                id: 'modulo-documentacion',
                label: 'MÓDULO 2',
                title: 'Documentación',
                icon: 'heroicon-o-folder-open',
                sections: [
                    $this->buildSection('Catálogos', 'heroicon-o-document-text', [
                        $this->resourceItem(
                            'Tipos de documento',
                            'heroicon-o-document-text',
                            TipoDocumentoResource::class,
                            TipoDocumento::class,
                        ),
                    ]),
                    $this->buildSection('Documentos por unidad', 'heroicon-o-document-duplicate', [
                        $this->resourceItem(
                            'Documentos por vehículo',
                            'heroicon-o-folder-open',
                            VehiculoDocumentoResource::class,
                            VehiculoDocumento::class,
                        ),
                    ]),
                ],
                description: 'Gestión documental por unidad.',
            ),
            $this->buildModule(
                id: 'modulo-mantenimiento',
                label: 'MÓDULO 3',
                title: 'Mantenimiento',
                icon: 'heroicon-o-wrench-screwdriver',
                sections: [],
                description: 'Disponible para futuras secciones.',
            ),
        ];
    }

    protected function buildModule(
        string $id,
        string $label,
        string $title,
        string $icon,
        array $sections,
        ?string $description = null,
    ): array {
        $visibleSections = array_values(array_filter($sections, fn (array $section): bool => filled($section['items'])));
        $isActive = collect($visibleSections)->contains(fn (array $section): bool => $section['active']);

        return [
            'id' => $id,
            'label' => $label,
            'title' => $title,
            'icon' => $icon,
            'description' => $description,
            'sections' => $visibleSections,
            'active' => $isActive,
        ];
    }

    protected function buildSection(string $title, string $icon, array $items): array
    {
        $visibleItems = array_values(array_filter($items, fn (?array $item): bool => filled($item)));
        $isActive = collect($visibleItems)->contains(fn (array $item): bool => $item['active']);

        return [
            'title' => $title,
            'icon' => $icon,
            'items' => $visibleItems,
            'active' => $isActive,
        ];
    }

    protected function resourceItem(
        string $label,
        string $icon,
        string $resource,
        array|string|null $abilityArguments = null,
        ?array $badge = null,
    ): ?array {
        if (! $this->canAccessResource($resource, $abilityArguments)) {
            return null;
        }

        return [
            'label' => $label,
            'icon' => $icon,
            'url' => $resource::getUrl(),
            'active' => request()->routeIs($resource::getRouteBaseName() . '.*'),
            'badge' => $badge,
        ];
    }

    protected function pageItem(
        string $label,
        string $icon,
        string $page,
        array|string|null $abilityArguments = null,
        ?array $badge = null,
    ): ?array {
        if (! $this->canAccessPage($page, $abilityArguments)) {
            return null;
        }

        return [
            'label' => $label,
            'icon' => $icon,
            'url' => $page::getUrl(),
            'active' => request()->routeIs($page::getRouteName()),
            'badge' => $badge,
        ];
    }

    protected function canAccessResource(string $resource, array|string|null $abilityArguments = null): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $arguments = $abilityArguments ?? $resource::getModel();

        return $user->can('viewAny', $arguments) || $resource::canAccess();
    }

    protected function canAccessPage(string $page, array|string|null $abilityArguments = null): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $canViewRelatedModel = filled($abilityArguments)
            ? $user->can('viewAny', $abilityArguments)
            : false;

        return $canViewRelatedModel || $page::canAccess();
    }

    protected function openAlertsBadge(): ?array
    {
        $user = Auth::user();

        if (! $user || ! $this->canAccessResource(AlertaRendimientoResource::class, AlertaRendimiento::class)) {
            return null;
        }

        $query = AlertaRendimiento::query()->where('estatus', 'Abierta');

        if ($user->hasRole('responsable')) {
            $query->where('responsable_user_id', $user->id);
        }

        $count = $query->count();

        if ($count < 1) {
            return null;
        }

        return [
            'label' => (string) $count,
            'color' => 'danger',
        ];
    }
}
