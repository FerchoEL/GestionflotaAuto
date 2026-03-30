<x-filament-panels::page>
    @php($vehiculos = $this->vehiculosAsignados())

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <div class="md:col-span-1">
            <x-filament::section heading="Vehículos asignados">
                @if($vehiculos->isEmpty())
                    <div class="text-sm text-gray-400">
                        No tienes vehículos asignados actualmente.
                    </div>
                @else
                    <div>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="vehiculoId">
                                @foreach($vehiculos as $v)
                                    <option value="{{ $v->id }}">
                                        {{ $v->numero_economico ?? '—' }} — {{ $v->placas }} — {{ $v->marca }} {{ $v->modelo }}
                                    </option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                @endif
            </x-filament::section>
        </div>

        <div class="md:col-span-2 space-y-4">
            @php($vehiculo = $this->vehiculoSeleccionado())

            <x-filament::section heading="Datos del vehículo">
                @if(!$vehiculo)
                    <div class="text-sm text-gray-400">
                        No hay vehículo seleccionado.
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                        <div><strong>Placas:</strong> {{ $vehiculo->placas }}</div>
                        <div><strong>No. Económico:</strong> {{ $vehiculo->numero_economico ?? '—' }}</div>
                        <div><strong>Marca:</strong> {{ $vehiculo->marca ?? '—' }}</div>

                        <div><strong>Modelo:</strong> {{ $vehiculo->modelo ?? '—' }}</div>
                        <div><strong>Año:</strong> {{ $vehiculo->anio ?? '—' }}</div>
                        <div><strong>Color:</strong> {{ $vehiculo->color ?? '—' }}</div>

                        <div><strong>Tipo:</strong> {{ $vehiculo->tipoVehiculo?->nombre ?? '—' }}</div>
                        <div><strong>Estatus:</strong> {{ $vehiculo->estatus?->nombre ?? '—' }}</div>
                        <div><strong>Departamento:</strong> {{ $vehiculo->departamentoActivo?->departamento?->nombre ?? '—' }}</div>

                        <div><strong>Localidad:</strong> {{ $vehiculo->localidadActiva?->localidad?->nombre ?? '—' }}</div>
                        <div><strong>Responsable:</strong> {{ $vehiculo->responsableActivo?->responsable?->name ?? '—' }}</div>
                        <div><strong>Chofer:</strong> {{ $vehiculo->choferActivo?->chofer?->name ?? '—' }}</div>

                        <div><strong>Capacidad tanque:</strong> {{ $vehiculo->capacidad_tanque_litros ?? '—' }} L</div>
                        <div><strong>Tipo combustible:</strong> {{ $vehiculo->tipo_combustible ?? '—' }}</div>
                        <div><strong>Transmisión:</strong> {{ $vehiculo->transmision ?? '—' }}</div>

                        <div><strong>Rend. óptimo:</strong> {{ $vehiculo->rendimiento_optimo_km_l ?? '—' }} km/L</div>
                        <div><strong>Tolerancia:</strong> {{ $vehiculo->tolerancia_pct ?? '—' }} %</div>
                    </div>
                @endif
            </x-filament::section>

            <x-filament::section heading="Alertas abiertas">
                @php($alertas = $this->alertasAbiertas())
                @if($alertas->isEmpty())
                    <div class="text-sm text-gray-400">Sin alertas abiertas.</div>
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

            <x-filament::section heading="Historial de rendimiento (50)">
                @php($historial = $this->historialRendimiento())

                @if($historial->isEmpty())
                    <div class="text-sm text-gray-400">
                        No hay historial disponible.
                    </div>
                @else
                    <div class="overflow-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b border-gray-700">
                                    <th class="py-2 pr-3">Fecha</th>
                                    <th class="py-2 pr-3">KM actuales</th>
                                    <th class="py-2 pr-3">KM recorridos</th>
                                    <th class="py-2 pr-3">Litros</th>
                                    <th class="py-2 pr-3">Rendimiento</th>
                                    <th class="py-2 pr-3">Precio/L</th>
                                    <th class="py-2 pr-3">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($historial as $row)
                                    <tr class="border-b border-gray-800">
                                        <td class="py-2 pr-3">{{ $row->fecha }}</td>
                                        <td class="py-2 pr-3">{{ $row->km_actuales }}</td>
                                        <td class="py-2 pr-3">{{ $row->km_recorridos }}</td>
                                        <td class="py-2 pr-3">{{ $row->litros }}</td>
                                        <td class="py-2 pr-3">{{ $row->rendimiento_km_l }}</td>
                                        <td class="py-2 pr-3">{{ $row->precio_litro }}</td>
                                        <td class="py-2 pr-3">{{ $row->importe }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>

            <x-filament::section heading="Documentos del vehículo">
                <div class="text-sm text-gray-400 space-y-1">
                    <div>Próximamente se mostrarán aquí:</div>
                    <div>• Tarjeta de circulación</div>
                    <div>• Póliza de seguro</div>
                    <div>• Verificación físico-mecánica</div>
                    <div>• Permisos y documentación adicional</div>
                </div>
            </x-filament::section>

        </div>
    </div>
</x-filament-panels::page>
