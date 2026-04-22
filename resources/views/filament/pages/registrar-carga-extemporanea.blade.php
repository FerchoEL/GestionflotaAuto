<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section
            heading="Corrección administrativa"
            description="Usa esta pantalla cuando una carga faltó en el flujo normal y necesitas insertarla en la secuencia histórica sin recapturar todo."
        >
            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button type="submit">
                        Registrar carga extemporánea
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
