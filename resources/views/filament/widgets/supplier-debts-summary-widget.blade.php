<x-filament::widget>
    <div class="p-4 bg-white rounded-lg shadow flex flex-col gap-2">
        <h3 class="text-lg font-bold">Synthèse des dettes fournisseurs</h3>
        <div class="flex flex-col gap-1">
            <div class="flex justify-between">
                <span>Total dettes</span>
                <span class="font-semibold text-red-600">{{ number_format($totalDebt, 0, ',', ' ') }} F CFA</span>
            </div>
            <div class="flex justify-between">
                <span>Total payé</span>
                <span class="font-semibold text-green-600">{{ number_format($totalPaid, 0, ',', ' ') }} F CFA</span>
            </div>
            <div class="flex justify-between">
                <span>Reste à payer</span>
                <span class="font-semibold text-orange-600">{{ number_format($totalRemaining, 0, ',', ' ') }}
                    F CFA</span>
            </div>
        </div>
    </div>
</x-filament::widget>
