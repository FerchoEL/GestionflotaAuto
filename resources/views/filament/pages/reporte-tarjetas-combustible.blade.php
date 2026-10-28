<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
        @foreach (\App\Reports\EstadoTarjetasReport::resumen() as $label => $value)
            <x-filament::section><div class="text-sm text-gray-500">{{ $label }}</div><div class="text-2xl font-bold">{{ $value }}</div></x-filament::section>
        @endforeach
    </div>
    <p class="text-sm text-gray-500">Indicadores del catálogo completo. Activo corresponde a la asignación. Cuenta analítica: cuenta vigente de la unidad, no la elegida en cargas. POSIBLE DUPLICIDAD indica múltiples asignaciones activas, relaciones faltantes o fechas incompatibles; consulte el historial para ver todas las unidades.</p>
    {{ $this->table }}
</x-filament-panels::page>
