<x-filament-panels::page>
    @php($vehiculos = $this->vehiculosAsignados())

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <div class="md:col-span-1">
            <x-filament::section heading="Vehículos asignados">
                @if($vehiculos->isEmpty())
                    <div class="text-sm text-gray-500">
                        No tienes vehículos asignados actualmente.
                    </div>
                @else
                    <x-filament::input.wrapper>
                        <select wire:model.live="vehiculoId" class="w-full rounded-md border-gray-300">
                            @foreach($vehiculos as $v)
                                <option value="{{ $v->id }}">
                                    {{ $v->placas }} — {{ $v->marca }} {{ $v->modelo }}
                                </option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                @endif
            </x-filament::section>
        </div>

        <div class="md:col-span-2 space-y-4">
            @php($vehiculo = $this->vehiculoSeleccionado())

            <x-filament::section heading="Datos del vehículo">
                @if(!$vehiculo)
                    <div class="text-sm text-gray-500">
                        No hay vehículo seleccionado.
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                        <div><strong>Placas:</strong> {{ $vehiculo->placas }}</div>
                        <div><strong>Tipo:</strong> {{ $vehiculo->tipoVehiculo?->nombre }}</div>
                        <div><strong>Estatus:</strong> {{ $vehiculo->estatus?->nombre }}</div>
                        <div><strong>Rend. óptimo:</strong> {{ $vehiculo->rendimiento_optimo_km_l }} km/L</div>

                        <div><strong>Departamento:</strong> {{ $vehiculo->departamentoActivo?->departamento?->nombre ?? '—' }}</div>
                        <div><strong>Localidad:</strong> {{ $vehiculo->localidadActiva?->localidad?->nombre ?? '—' }}</div>
                        <div class="md:col-span-2">
                            <strong>Responsable:</strong>
                            {{ $vehiculo->responsableActivo?->responsable?->name ?? '—' }}
                        </div>
                    </div>
                @endif
            </x-filament::section>

            <x-filament::section heading="Alertas abiertas">
                @php($alertas = $this->alertasAbiertas())
                @if($alertas->isEmpty())
                    <div class="text-sm text-gray-500">Sin alertas abiertas.</div>
                @else
                    <ul class="list-disc pl-5 text-sm">
                        @foreach($alertas as $a)
                            <li>
                                {{ $a->fecha_alerta }} —
                                Rend: {{ $a->rendimiento_detectado }}
                                (Opt: {{ $a->rendimiento_optimo }})
                                — {{ $a->estatus }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-filament::section>

            <x-filament::section heading="Últimas cargas (50)">
                @php($cargas = $this->cargas())
                @if($cargas->isEmpty())
                    <div class="text-sm text-gray-500">No hay cargas registradas.</div>
                @else
                    <div class="overflow-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left">
                                    <th class="py-2">Fecha</th>
                                    <th>KM</th>
                                    <th>Litros</th>
                                    <th>Precio/L</th>
                                    <th>Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cargas as $c)
                                    <tr class="border-t">
                                        <td class="py-2">{{ $c->fecha_carga }}</td>
                                        <td>{{ $c->km_odometro }}</td>
                                        <td>{{ $c->litros }}</td>
                                        <td>{{ $c->precio_litro }}</td>
                                        <td>{{ $c->importe }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>

            <x-filament::section heading="Historial de rendimiento (50)">
                @php($rends = $this->rendimientos())
                @if($rends->isEmpty())
                    <div class="text-sm text-gray-500">No hay rendimientos calculados.</div>
                @else
                    <div class="overflow-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left">
                                    <th class="py-2">Fecha</th>
                                    <th>KM recorridos</th>
                                    <th>KM/L</th>
                                    <th>Evaluado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rends as $r)
                                    <tr class="border-t">
                                        <td class="py-2">{{ $r->created_at }}</td>
                                        <td>{{ $r->km_recorridos }}</td>
                                        <td>{{ $r->rendimiento_km_l }}</td>
                                        <td>{{ $r->evaluado ? 'Sí' : 'No' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>

        </div>
    </div>
</x-filament-panels::page>