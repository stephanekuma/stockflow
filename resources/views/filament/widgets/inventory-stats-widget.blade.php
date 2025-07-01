@php($stats = $this->getStats())
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 w-full">
    <div class="bg-blue-50 rounded shadow p-4 flex items-center gap-4">
        <span class="bg-blue-100 p-2 rounded-full">
            <!-- Heroicon: Cube -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20.5 7.5l-8.5-4.5-8.5 4.5m17 0v9a2 2 0 01-1 1.73l-7.5 4.27a2 2 0 01-2 0l-7.5-4.27A2 2 0 013 16.5v-9" />
            </svg>
        </span>
        <div>
            <div class="text-gray-500 text-xs">Total stock</div>
            <div class="text-2xl font-bold text-blue-800">{{ $stats['totalStock'] ?? 0 }}</div>
        </div>
    </div>
    <div class="bg-green-50 rounded shadow p-4 flex items-center gap-4">
        <span class="bg-green-100 p-2 rounded-full">
            <!-- Heroicon: CurrencyDollar -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8c-2.21 0-4 1.343-4 3s1.79 3 4 3 4-1.343 4-3-1.79-3-4-3zm0 0V4m0 12v4" />
            </svg>
        </span>
        <div>
            <div class="text-gray-500 text-xs">Valeur achat</div>
            <div class="text-2xl font-bold text-green-800">
                {{ number_format($stats['totalPurchaseValue'] ?? 0, 0, ',', ' ') }} XOF</div>
        </div>
    </div>
    <div class="bg-yellow-50 rounded shadow p-4 flex items-center gap-4">
        <span class="bg-yellow-100 p-2 rounded-full">
            <!-- Heroicon: Tag -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7l10 10M7 17l10-10" />
            </svg>
        </span>
        <div>
            <div class="text-gray-500 text-xs">Valeur vente</div>
            <div class="text-2xl font-bold text-yellow-800">
                {{ number_format($stats['totalSaleValue'] ?? 0, 0, ',', ' ') }} XOF</div>
        </div>
    </div>
    <div class="bg-purple-50 rounded shadow p-4 flex items-center gap-4">
        <span class="bg-purple-100 p-2 rounded-full">
            <!-- Heroicon: ShoppingCart -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.35 2.7A1 1 0 007 17h10a1 1 0 00.95-.68l3.24-7.24A1 1 0 0020 8H6.21" />
            </svg>
        </span>
        <div>
            <div class="text-gray-500 text-xs">Total achetés</div>
            <div class="text-2xl font-bold text-purple-800">{{ $stats['totalPurchased'] ?? 0 }}</div>
        </div>
    </div>
</div>
