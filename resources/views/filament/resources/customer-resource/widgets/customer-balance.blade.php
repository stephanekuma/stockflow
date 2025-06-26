<div class="flex flex-col md:flex-row gap-4 mb-6">
    <div class="flex-1 bg-white rounded-lg shadow p-4 border border-gray-100">
        <div class="text-xs text-gray-500 mb-1">Solde disponible</div>
        <div class="text-2xl font-bold text-green-600">{{ number_format($customer->balance, 2) }} XOF</div>
    </div>
    <div class="flex-1 bg-white rounded-lg shadow p-4 border border-gray-100">
        <div class="text-xs text-gray-500 mb-1">Solde dû (crédit)</div>
        <div class="text-2xl font-bold text-red-600">
            {{ number_format($customer->sales->sum(fn($sale) => $sale->amount_due), 2) }} XOF</div>
    </div>
</div>
