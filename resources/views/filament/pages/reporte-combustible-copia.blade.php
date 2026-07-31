<x-filament-panels::page>

<div class="space-y-6">

{{-- ========================
   FILTROS
======================== --}}

<x-filament::section heading="Filtros">

<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-3">

{{-- VEHICULO --}}
<x-filament::input.wrapper>
<x-filament::input.select wire:model.live="vehiculo_id">

<option value="">Vehículo</option>

@foreach(\App\Models\Vehiculo::orderBy('placas')->get() as $v)

<option value="{{ $v->id }}">
{{ $v->placas }} | {{ $v->numero_economico }}
</option>

@endforeach

</x-filament::input.select>
</x-filament::input.wrapper>


{{-- CUENTA ANALITICA --}}
<x-filament::input.wrapper>
<x-filament::input.select wire:model.live="cuenta_analitica_id">

<option value="">Cuenta analítica</option>

@foreach(\App\Models\CuentaAnalitica::where('activo',true)->get() as $c)

<option value="{{ $c->id }}">
{{ $c->nombre }}
</option>

@endforeach

</x-filament::input.select>
</x-filament::input.wrapper>


{{-- DEPARTAMENTO --}}
<x-filament::input.wrapper>
<x-filament::input.select wire:model.live="departamento_id">

<option value="">Departamento</option>

@foreach(\App\Models\Departamento::orderBy('nombre')->get() as $d)

<option value="{{ $d->id }}">
{{ $d->nombre }}
</option>

@endforeach

</x-filament::input.select>
</x-filament::input.wrapper>


{{-- LOCALIDAD --}}
<x-filament::input.wrapper>
<x-filament::input.select wire:model.live="localidad_id">

<option value="">Localidad</option>

@foreach(\App\Models\Localidad::orderBy('nombre')->get() as $l)

<option value="{{ $l->id }}">
{{ $l->nombre }}
</option>

@endforeach

</x-filament::input.select>
</x-filament::input.wrapper>


{{-- TIPO COMBUSTIBLE --}}
<x-filament::input.wrapper>
<x-filament::input.select wire:model.live="tipo_combustible">

<option value="">Tipo Combustible</option>
<option value="diesel">Diesel</option>
<option value="gasolina">Gasolina</option>

</x-filament::input.select>
</x-filament::input.wrapper>


{{-- FECHA INICIO --}}
<x-filament::input.wrapper>

<x-filament::input
type="date"
wire:model.live="fecha_inicio"
/>

</x-filament::input.wrapper>


{{-- FECHA FIN --}}
<x-filament::input.wrapper>

<x-filament::input
type="date"
wire:model.live="fecha_fin"
/>

</x-filament::input.wrapper>

</div>

</x-filament::section>


{{-- ========================
   BOTON EXPORTAR
======================== --}}

<div>

<x-filament::button
wire:click="exportar"
color="success"
>

Exportar Excel

</x-filament::button>

</div>


{{-- ========================
   TABLA
======================== --}}

<x-filament::section heading="Reporte de Combustible Copia">

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
@if($vehiculo_id)
<th class="whitespace-nowrap px-3 py-3 text-left">Fecha</th>
@endif
<th class="whitespace-nowrap px-3 py-3 text-center">Tarjeta</th>
<th class="whitespace-nowrap px-3 py-3 text-right">{{ $vehiculo_id ? 'Odómetro' : 'Odómetro inicial' }}</th>
<th class="whitespace-nowrap px-3 py-3 text-right">{{ $vehiculo_id ? 'Odómetro anterior' : 'Odómetro final' }}</th>
<th class="whitespace-nowrap px-3 py-3 text-right">KM recorridos</th>
<th class="whitespace-nowrap px-3 py-3 text-right">Litros cargados</th>
<th class="whitespace-nowrap px-3 py-3 text-right">Rendimiento Real</th>
<th class="whitespace-nowrap px-3 py-3 text-right">Rendimiento Óptimo</th>
@if($vehiculo_id)
<th class="whitespace-nowrap px-3 py-3 text-right">Precio/L</th>
@endif
<th class="whitespace-nowrap px-3 py-3 text-right">Importe</th>
@if($vehiculo_id)
<th class="whitespace-nowrap px-3 py-3 text-left">Cuenta Analítica</th>
@endif

</tr>

</thead>


<tbody>

{{-- ========================
   RESUMEN POR VEHICULO
======================== --}}

@if(!$vehiculo_id)

@foreach($this->resumenVehiculos() as $v)

<tr class="border-b align-middle hover:bg-gray-50 dark:hover:bg-gray-800/60">

<td class="whitespace-nowrap px-3 py-3 text-center">{{ $v->numero_economico }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left font-medium">{{ $v->placas }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $v->marca ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $v->modelo ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $v->localidad ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $v->departamento ?? '-' }}</td>
<td class="px-3 py-3 text-left">{{ $v->usuarios_asignados ?? '-' }}</td>
<td class="px-3 py-3 text-left">{{ $v->usuario_responsable ?? '-' }}</td>
@if($vehiculo_id)
<td class="whitespace-nowrap px-3 py-3 text-left text-gray-500">-</td>
@endif
<td class="whitespace-nowrap px-3 py-3 text-center">{{ $v->tarjeta ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-right">
{{ $v->odometro_inicial !== null ? number_format($v->odometro_inicial, 0) : '-' }}
</td>
<td class="whitespace-nowrap px-3 py-3 text-right">
{{ $v->odometro_final !== null ? number_format($v->odometro_final, 0) : '-' }}
</td>
<td class="whitespace-nowrap px-3 py-3 text-right">{{ number_format($v->km_recorridos,0) }}</td>
<td class="whitespace-nowrap px-3 py-3 text-right">{{ number_format($v->litros_cargados,3) }}</td>
<td class="whitespace-nowrap px-3 py-3 text-right">

@if($v->rendimiento_real !== null)
{{ number_format($v->rendimiento_real,2) }}
@else
0
@endif

</td>

<td class="whitespace-nowrap px-3 py-3 text-right">{{ number_format($v->rendimiento_optimo_km_l,2) }}</td>

<td class="whitespace-nowrap px-3 py-3 text-right font-medium">{{ number_format($v->importe,2) }}</td>

@if($vehiculo_id)
<td class="whitespace-nowrap px-3 py-3 text-left text-gray-500">-</td>
@endif

</tr>

@endforeach

@else


{{-- ========================
   DETALLE POR CARGA
======================== --}}

@foreach($this->cargas() as $c)

<tr class="border-b align-middle hover:bg-gray-50 dark:hover:bg-gray-800/60">

<td class="whitespace-nowrap px-3 py-3 text-center">{{ $c->vehiculo?->numero_economico }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left font-medium">{{ $c->vehiculo?->placas }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $c->vehiculo?->marca ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $c->vehiculo?->modelo ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $c->vehiculo?->localidadActiva?->localidad?->nombre ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $c->vehiculo?->departamentoActivo?->departamento?->nombre ?? '-' }}</td>
<td class="px-3 py-3 text-left">{{ $c->vehiculo?->usuarios_asignados_texto ?? '-' }}</td>
<td class="px-3 py-3 text-left">{{ $c->vehiculo?->usuario_responsable_texto ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-left">{{ $c->fecha_carga }}</td>
<td class="whitespace-nowrap px-3 py-3 text-center">{{ $c->vehiculo?->tarjetaActiva?->tarjeta?->numero ?? '-' }}</td>
<td class="whitespace-nowrap px-3 py-3 text-right">{{ $c->km_odometro }}</td>
<td class="whitespace-nowrap px-3 py-3 text-right">
{{ $c->odometro_anterior_reporte !== null ? number_format($c->odometro_anterior_reporte, 0) : '-' }}
</td>

<td class="whitespace-nowrap px-3 py-3 text-right">
{{ $c->km_recorridos_reporte !== null ? number_format($c->km_recorridos_reporte, 0) : '-' }}
</td>

<td class="whitespace-nowrap px-3 py-3 text-right">{{ number_format($c->litros, 3) }}</td>

<td class="whitespace-nowrap px-3 py-3 text-right">
{{ $c->rendimiento_real_reporte !== null ? number_format($c->rendimiento_real_reporte, 2) : '-' }}
</td>

<td class="whitespace-nowrap px-3 py-3 text-right">{{ number_format($c->vehiculo?->rendimiento_optimo_km_l,2) }}</td>

<td class="whitespace-nowrap px-3 py-3 text-right">{{ $c->precio_litro }}</td>

<td class="whitespace-nowrap px-3 py-3 text-right font-medium">{{ number_format($c->importe,2) }}</td>

<td class="whitespace-nowrap px-3 py-3 text-left">{{ $c->cuentaAnalitica?->nombre }}</td>

</tr>

@endforeach

@endif

</tbody>


{{-- ========================
   TOTALES
======================== --}}

<tfoot>

<tr class="border-t bg-gray-50 font-bold dark:bg-gray-800">

<td colspan="{{ $vehiculo_id ? 12 : 11 }}" class="whitespace-nowrap px-3 py-3 text-left">

TOTALES

</td>

<td class="whitespace-nowrap px-3 py-3 text-right">

{{ number_format($this->totalKm(),0) }}

</td>

<td class="whitespace-nowrap px-3 py-3 text-right">

{{ number_format($this->totalLitrosCargados(),3) }}

</td>

<td class="whitespace-nowrap px-3 py-3 text-right">

{{ number_format($this->rendimientoGlobal(),2) }}

</td>

<td class="px-3 py-3"></td>

@if($vehiculo_id)
<td class="px-3 py-3"></td>
@endif

<td class="whitespace-nowrap px-3 py-3 text-right">

$ {{ number_format($this->totalImporte(),2) }}

</td>

@if($vehiculo_id)
<td class="px-3 py-3"></td>
@endif

</tr>

</tfoot>

</table>

</div>

</x-filament::section>

</div>

</x-filament-panels::page>
