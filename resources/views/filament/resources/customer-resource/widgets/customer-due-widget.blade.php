<x-filament::widget>
    <div class="p-4">
        <div class="text-lg font-bold">Montant total dû</div>
        <div class="text-2xl text-danger font-extrabold">{{ number_format($due, 0, ',', ' ') }} XOF</div>
    </div>
</x-filament::widget>
