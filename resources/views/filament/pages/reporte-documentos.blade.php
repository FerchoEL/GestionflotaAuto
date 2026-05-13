<x-filament-panels::page>

<div class="space-y-6">

<x-filament::section heading="Filtros">

<div class="grid grid-cols-1 gap-3 md:grid-cols-3 lg:grid-cols-7">

<x-filament::input.wrapper>
<x-filament::input.select wire:model.live="vehiculo_id">
<option value="">Vehículo</option>
@foreach(\App\Models\Vehiculo::orderBy('placas')->get() as $vehiculo)
<option value="{{ $vehiculo->id }}">{{ $vehiculo->placas }} | {{ $vehiculo->numero_economico }}</option>
@endforeach
</x-filament::input.select>
</x-filament::input.wrapper>

<x-filament::input.wrapper>
<x-filament::input.select wire:model.live="tipo_documento_id">
<option value="">Tipo de documento</option>
@foreach(\App\Models\TipoDocumento::orderBy('nombre')->get() as $tipo)
<option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
@endforeach
</x-filament::input.select>
</x-filament::input.wrapper>

<x-filament::input.wrapper>
<x-filament::input.select wire:model.live="departamento_id">
<option value="">Departamento</option>
@foreach(\App\Models\Departamento::orderBy('nombre')->get() as $departamento)
<option value="{{ $departamento->id }}">{{ $departamento->nombre }}</option>
@endforeach
</x-filament::input.select>
</x-filament::input.wrapper>

<x-filament::input.wrapper>
<x-filament::input.select wire:model.live="localidad_id">
<option value="">Localidad</option>
@foreach(\App\Models\Localidad::orderBy('nombre')->get() as $localidad)
<option value="{{ $localidad->id }}">{{ $localidad->nombre }}</option>
@endforeach
</x-filament::input.select>
</x-filament::input.wrapper>

<x-filament::input.wrapper>
<x-filament::input.select wire:model.live="estado_vigencia">
<option value="">Estado de vigencia</option>
<option value="vigente">Vigente</option>
<option value="por_vencer">Por vencer</option>
<option value="vencido">Vencido</option>
<option value="sin_vigencia">Sin vigencia / no aplica</option>
</x-filament::input.select>
</x-filament::input.wrapper>

<x-filament::input.wrapper>
<x-filament::input type="date" wire:model.live="fecha_vencimiento_inicio" />
</x-filament::input.wrapper>

<x-filament::input.wrapper>
<x-filament::input type="date" wire:model.live="fecha_vencimiento_fin" />
</x-filament::input.wrapper>

</div>

</x-filament::section>

<div class="grid grid-cols-2 gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5">
@php($resumen = $this->resumen())

<x-filament::section>
<div class="text-sm text-gray-500">Total documentos</div>
<div class="text-2xl font-semibold">{{ number_format($resumen['total']) }}</div>
</x-filament::section>

<x-filament::section>
<div class="text-sm text-gray-500">Vigentes</div>
<div class="text-2xl font-semibold text-success-600">{{ number_format($resumen['vigentes']) }}</div>
</x-filament::section>

<x-filament::section>
<div class="text-sm text-gray-500">Por vencer</div>
<div class="text-2xl font-semibold text-warning-600">{{ number_format($resumen['por_vencer']) }}</div>
</x-filament::section>

<x-filament::section>
<div class="text-sm text-gray-500">Vencidos</div>
<div class="text-2xl font-semibold text-danger-600">{{ number_format($resumen['vencidos']) }}</div>
</x-filament::section>

<x-filament::section>
<div class="text-sm text-gray-500">Sin vigencia / no aplica</div>
<div class="text-2xl font-semibold text-gray-700">{{ number_format($resumen['sin_vigencia']) }}</div>
</x-filament::section>
</div>

<div>
<x-filament::button wire:click="exportar" color="success">
Exportar Excel
</x-filament::button>
</div>

<x-filament::section heading="Reporte de Documentos">

<div class="overflow-auto">
<table class="w-full min-w-max border-separate border-spacing-0 text-sm">
<thead>
<tr class="border-b bg-gray-50 text-xs font-semibold uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
<th class="whitespace-nowrap px-3 py-3 text-center">No. Económico</th>
<th class="whitespace-nowrap px-3 py-3 text-left">Placa</th>
<th class="whitespace-nowrap px-3 py-3 text-left">Marca</th>
<th class="whitespace-nowrap px-3 py-3 text-left">Modelo</th>
<th class="whitespace-nowrap px-3 py-3 text-left">Localidad</th>
<th class="whitespace-nowrap px-3 py-3 text-left">Departamento</th>
<th class="whitespace-nowrap px-3 py-3 text-left">Usuarios asignados</th>
<th class="whitespace-nowrap px-3 py-3 text-left">Usuario responsable</th>
<th class="whitespace-nowrap px-3 py-3 text-left">Tipo documento</th>
<th class="whitespace-nowrap px-3 py-3 text-left">Nombre documento</th>
<th class="whitespace-nowrap px-3 py-3 text-center">Emisión</th>
<th class="whitespace-nowrap px-3 py-3 text-center">Vencimiento</th>
<th class="whitespace-nowrap px-3 py-3 text-center">Estado</th>
<th class="whitespace-nowrap px-3 py-3 text-right">Días para vencer</th>
<th class="whitespace-nowrap px-3 py-3 text-left">Aseguradora</th>
<th class="whitespace-nowrap px-3 py-3 text-right">Costo póliza</th>
<th class="whitespace-nowrap px-3 py-3 text-left">Tipo de pago</th>
<th class="whitespace-nowrap px-3 py-3 text-center">Archivo</th>
</tr>
</thead>
<tbody>
@forelse($this->documentos() as $documento)
<tr class="border-b align-middle hover:bg-gray-50 dark:hover:bg-gray-800/60">
<td class="whitespace-nowrap px-3 py-3 text-center">{{ $documento->vehiculo?->numero_economico ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left font-medium">{{ $documento->vehiculo?->placas ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $documento->vehiculo?->marca ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $documento->vehiculo?->modelo ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $documento->vehiculo?->localidadActiva?->localidad?->nombre ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $documento->vehiculo?->departamentoActivo?->departamento?->nombre ?? '-' }}</td>
<td class="px-3 py-3 text-left">{{ $documento->vehiculo?->usuarios_asignados_texto ?? '-' }}</td>
<td class="px-3 py-3 text-left">{{ $documento->vehiculo?->usuario_responsable_texto ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $documento->tipoDocumento?->nombre ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $documento->nombre }}</td>
<td class="whitespace-nowrap px-3 py-3 text-center">{{ $documento->fecha_emision?->format('d/m/Y') ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-center">{{ $documento->fecha_vencimiento?->format('d/m/Y') ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-center">
@php($estado = $documento->estado_vigencia_reporte)
<span @class([
    'inline-flex rounded-full px-2 py-1 text-xs font-medium',
    'bg-success-50 text-success-700' => $estado === 'vigente',
    'bg-warning-50 text-warning-700' => $estado === 'por_vencer',
    'bg-danger-50 text-danger-700' => $estado === 'vencido',
    'bg-gray-100 text-gray-700' => $estado === 'sin_vigencia',
])>
{{ match($estado) {
    'vigente' => 'Vigente',
    'por_vencer' => 'Por vencer',
    'vencido' => 'Vencido',
    default => 'Sin vigencia / no aplica',
} }}
</span>
</td>
<td class="whitespace-nowrap px-3 py-3 text-right">
{{ $documento->dias_para_vencer_reporte !== null ? number_format($documento->dias_para_vencer_reporte) : '-' }}
</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $documento->polizaSeguro?->aseguradora?->nombre ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-right">{{ $documento->polizaSeguro?->costo_poliza !== null ? number_format((float) $documento->polizaSeguro->costo_poliza, 2) : '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $documento->polizaSeguro?->tipoPago?->nombre ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-center">
@if($documento->archivo_path)
<a
    href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($documento->archivo_path) }}"
    target="_blank"
    class="text-primary-600 hover:underline"
>
Ver archivo
</a>
@else
-
@endif
</td>
</tr>
@empty
<tr>
<td colspan="18" class="px-3 py-6 text-center text-sm text-gray-500">
No se encontraron documentos con los filtros seleccionados.
</td>
</tr>
@endforelse
</tbody>
</table>
</div>

</x-filament::section>

</div>

</x-filament-panels::page>
