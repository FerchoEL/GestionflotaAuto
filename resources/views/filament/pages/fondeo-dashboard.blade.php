<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl border p-4 bg-white dark:bg-gray-900">
            <div class="text-sm text-gray-500">Críticos</div>
            <div class="mt-1 text-3xl font-bold text-red-600">
                ⚠ {{ $this->getCriticosCount() }}
            </div>
            <div class="text-xs text-gray-500 mt-1">
                Saldo ≤ 0 o % < 40
            </div>
        </div>

        <div class="rounded-xl border p-4 bg-white dark:bg-gray-900">
            <div class="text-sm text-gray-500">Atención</div>
            <div class="mt-1 text-3xl font-bold text-yellow-600">
                🟡 {{ $this->getAtencionCount() }}
            </div>
            <div class="text-xs text-gray-500 mt-1">
                40% – 69%
            </div>
        </div>

        <div class="rounded-xl border p-4 bg-white dark:bg-gray-900">
            <div class="text-sm text-gray-500">Saludables</div>
            <div class="mt-1 text-3xl font-bold text-green-600">
                🟢 {{ $this->getSaludablesCount() }}
            </div>
            <div class="text-xs text-gray-500 mt-1">
                ≥ 70%
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>