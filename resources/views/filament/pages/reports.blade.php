<x-filament-panels::page>
    {{ $this->form }}

    <div class="flex gap-3 mt-6">
        <x-filament::button wire:click="inventoryPdf" icon="heroicon-o-archive-box">
            Reporte de inventario
        </x-filament::button>

        <x-filament::button wire:click="salesPdf" icon="heroicon-o-currency-dollar" color="success">
            Reporte de ventas
        </x-filament::button>
    </div>
</x-filament-panels::page>