<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4 dark:bg-gray-900">
            <div class="text-sm text-gray-500">Criticas</div>
            <div class="mt-1 text-3xl font-bold text-red-600">
                {{ $this->getCriticasCount() }}
            </div>
            <div class="mt-1 text-xs text-gray-500">
                Saldo <= 0 o porcentaje < 40
            </div>
        </div>

        <div class="rounded-xl border bg-white p-4 dark:bg-gray-900">
            <div class="text-sm text-gray-500">Atencion</div>
            <div class="mt-1 text-3xl font-bold text-yellow-600">
                {{ $this->getAtencionCount() }}
            </div>
            <div class="mt-1 text-xs text-gray-500">
                Entre 40% y 69%
            </div>
        </div>

        <div class="rounded-xl border bg-white p-4 dark:bg-gray-900">
            <div class="text-sm text-gray-500">Saludables</div>
            <div class="mt-1 text-3xl font-bold text-green-600">
                {{ $this->getSaludablesCount() }}
            </div>
            <div class="mt-1 text-xs text-gray-500">
                Igual o mayor a 70%
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
