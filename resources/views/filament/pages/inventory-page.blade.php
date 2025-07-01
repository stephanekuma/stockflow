@php($stats = app(\App\Filament\Pages\InventoryPage::class)->getStats())

<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded shadow p-4">
            <div class="text-gray-500 text-xs">Total stock</div>
            <div class="text-2xl font-bold">{{ $stats['totalStock'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded shadow p-4">
            <div class="text-gray-500 text-xs">Valeur achat</div>
            <div class="text-2xl font-bold">{{ number_format($stats['totalPurchaseValue'] ?? 0, 0, ',', ' ') }} XOF</div>
        </div>
        <div class="bg-white rounded shadow p-4">
            <div class="text-gray-500 text-xs">Valeur vente</div>
            <div class="text-2xl font-bold">{{ number_format($stats['totalSaleValue'] ?? 0, 0, ',', ' ') }} XOF</div>
        </div>
        <div class="bg-white rounded shadow p-4">
            <div class="text-gray-500 text-xs">Total achetés</div>
            <div class="text-2xl font-bold">{{ $stats['totalPurchased'] ?? 0 }}</div>
        </div>
    </div>
    {{ $this->table }}
</x-filament::page>
