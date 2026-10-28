<x-filament-panels::page>
    <p>Tarjeta: {{ $numero }}. La cuenta histórica se obtiene del periodo de cuenta de la unidad al inicio de la asignación; si falta o es ambiguo se muestra SIN INFORMACIÓN.</p>
    <a href="{{ \App\Filament\Pages\ReporteTarjetasCombustible::getUrl() }}" class="text-primary-600">Volver al reporte</a>
    {{ $this->table }}
</x-filament-panels::page>
