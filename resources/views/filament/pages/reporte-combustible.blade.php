<x-filament-panels::page>

<div class="space-y-6">

{{-- ========================
   FILTROS
======================== --}}

<x-filament::section heading="Filtros">

<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">

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

<x-filament::section heading="Reporte de Combustible">

<div class="overflow-auto">

<table class="w-full text-sm">

<thead>

<tr class="border-b text-left">

<th>Fecha</th>
<th>Vehículo</th>
<th>No Económico</th>
<th>Tarjeta</th>
<th>KM</th>
<th>KM recorridos</th>
<th>Litros</th>
<th>Rendimiento</th>
<th>Precio/L</th>
<th>Importe</th>
<th>Cuenta Analítica</th>

</tr>

</thead>


<tbody>

{{-- ========================
   RESUMEN POR VEHICULO
======================== --}}

@if(!$vehiculo_id)

@foreach($this->resumenVehiculos() as $v)

<tr class="border-b">

<td>-</td>

<td>{{ $v->placas }}</td>

<td>{{ $v->numero_economico }}</td>

<td>-</td>

<td>-</td>

<td>{{ number_format($v->km_recorridos,0) }}</td>

<td>{{ number_format($v->litros,2) }}</td>

<td>

@if($v->litros > 0)
{{ number_format($v->km_recorridos / $v->litros,2) }}
@else
0
@endif

</td>

<td>-</td>

<td>{{ number_format($v->importe,2) }}</td>

<td>-</td>

</tr>

@endforeach

@else


{{-- ========================
   DETALLE POR CARGA
======================== --}}

@foreach($this->cargas() as $c)

<tr class="border-b">

<td>{{ $c->fecha_carga }}</td>

<td>{{ $c->vehiculo?->placas }}</td>

<td>{{ $c->vehiculo?->numero_economico }}</td>

<td>{{ $c->vehiculo?->tarjetaActiva?->tarjeta?->numero ?? '-' }}</td>

<td>{{ $c->km_odometro }}</td>

<td>{{ $c->rendimiento?->km_recorridos }}</td>

<td>{{ $c->litros }}</td>

<td>{{ $c->rendimiento?->rendimiento_km_l }}</td>

<td>{{ $c->precio_litro }}</td>

<td>{{ number_format($c->importe,2) }}</td>

<td>{{ $c->cuentaAnalitica?->nombre }}</td>

</tr>

@endforeach

@endif

</tbody>


{{-- ========================
   TOTALES
======================== --}}

<tfoot>

<tr class="font-bold border-t">

<td colspan="5">

TOTALES

</td>

<td>

{{ number_format($this->totalKm(),0) }}

</td>

<td>

{{ number_format($this->totalLitros(),2) }}

</td>

<td>

{{ number_format($this->rendimientoGlobal(),2) }}

</td>

<td></td>

<td>

$ {{ number_format($this->totalImporte(),2) }}

</td>

<td></td>

</tr>

</tfoot>

</table>

</div>

</x-filament::section>

</div>

</x-filament-panels::page>