@php($filters = $filters ?? [])
@php($results = $results ?? [])
@php($customers = $customers ?? [])
@php($providers = $providers ?? [])

<x-filament::page>
    <div class="flex items-center gap-2 mb-6">
        <svg class="w-7 h-7 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" stroke-width="1.5"
            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3.75 4.5A2.25 2.25 0 0 1 6 2.25h3A2.25 2.25 0 0 1 11.25 4.5v3A2.25 2.25 0 0 1 9 9.75H6A2.25 2.25 0 0 1 3.75 7.5v-3zM3.75 16.5A2.25 2.25 0 0 1 6 14.25h3a2.25 2.25 0 0 1 2.25 2.25v3A2.25 2.25 0 0 1 9 21.75H6A2.25 2.25 0 0 1 3.75 19.5v-3zM13.5 4.5A2.25 2.25 0 0 1 15.75 2.25h3A2.25 2.25 0 0 1 21 4.5v3a2.25 2.25 0 0 1-2.25 2.25h-3A2.25 2.25 0 0 1 13.5 7.5v-3zM13.5 16.5a2.25 2.25 0 0 1 2.25-2.25h3A2.25 2.25 0 0 1 21 16.5v3a2.25 2.25 0 0 1-2.25 2.25h-3A2.25 2.25 0 0 1 13.5 19.5v-3z">
            </path>
        </svg>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Rapports</h1>
    </div>

    <div class="mb-6">
        <form wire:submit.prevent="applyFilters" class="flex flex-wrap gap-4 items-end">
            <div class="space-y-2 w-48">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Type de rapport</label>
                <select wire:model.defer="filters.type"
                    class="filament-input w-full bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 rounded-lg">
                    <option value="sales">Ventes</option>
                    <option value="payments">Paiements</option>
                    <option value="deposits">Dépôts</option>
                    <option value="purchases">Achats</option>
                    <option value="stock">Mouvements de stock</option>
                </select>
            </div>
            <div class="space-y-2 w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Du</label>
                <input type="date" wire:model.defer="filters.date_from"
                    class="filament-input w-full bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 rounded-lg" />
            </div>
            <div class="space-y-2 w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Au</label>
                <input type="date" wire:model.defer="filters.date_to"
                    class="filament-input w-full bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 rounded-lg" />
            </div>
            <div class="space-y-2 w-48">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Client</label>
                <select wire:model.defer="filters.customer_id"
                    class="filament-input w-full bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 rounded-lg">
                    <option value="">Tous</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-2 w-48">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Fournisseur</label>
                <select wire:model.defer="filters.provider_id"
                    class="filament-input w-full bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 rounded-lg">
                    <option value="">Tous</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end h-full">
                <x-filament::button type="submit" color="primary">Filtrer</x-filament::button>
            </div>
        </form>
    </div>

    @if (($filters['type'] ?? 'sales') === 'sales')
        <div
            class="rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 mb-6 w-full">
            <div class="flex items-center justify-between px-6 pt-6 pb-2">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Ventes</h2>
            </div>
            <div class="overflow-x-auto mt-4">
                <table class="w-full min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Date</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Client</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Montant total</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Remise</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Net à payer</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($results['sales'] ?? [] as $sale)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $sale->sold_at ? $sale->sold_at->format('d/m/Y') : '' }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $sale->customer->name ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ number_format($sale->subtotal, 0, ',', ' ') }} XOF</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ number_format($sale->discount, 0, ',', ' ') }} XOF</td>
                                <td class="px-4 py-2 font-bold text-gray-900 dark:text-gray-100">
                                    {{ number_format($sale->total, 0, ',', ' ') }} XOF</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('filament.admin.resources.sales.edit', ['tenant' => $sale->store_id, 'record' => $sale->id]) }}"
                                        class="inline-flex items-center gap-1 text-primary-600 dark:text-primary-400 hover:underline font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                                        </svg>
                                        Voir
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-400 dark:text-gray-500 py-4">Aucune
                                    vente trouvée pour ces filtres.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 dark:bg-gray-800 font-bold">
                            <td colspan="4" class="px-4 py-2 text-right">Total</td>
                            <td class="px-4 py-2">
                                {{ number_format(collect($results['sales'] ?? [])->sum('total'), 0, ',', ' ') }} XOF
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @elseif(($filters['type'] ?? '') === 'payments')
        <div
            class="rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 mb-6 w-full">
            <div class="flex items-center justify-between px-6 pt-6 pb-2">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Paiements</h2>
            </div>
            <div class="overflow-x-auto mt-4">
                <table class="w-full min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Date</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Client</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Montant</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Vente liée</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($results['payments'] ?? [] as $payment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y') : '' }}
                                </td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $payment->customer->name ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ number_format($payment->amount, 0, ',', ' ') }} XOF</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    @if ($payment->sale)
                                        <a href="{{ route('filament.admin.resources.sales.edit', ['tenant' => $payment->sale->store_id, 'record' => $payment->sale->id]) }}"
                                            class="text-primary-600 dark:text-primary-400 hover:underline">Vente
                                            #{{ $payment->sale->id }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-gray-400 dark:text-gray-500 py-4">Aucun
                                    paiement trouvé pour ces filtres.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 dark:bg-gray-800 font-bold">
                            <td colspan="2" class="px-4 py-2 text-right">Total</td>
                            <td class="px-4 py-2">
                                {{ number_format(collect($results['payments'] ?? [])->sum('amount'), 0, ',', ' ') }}
                                XOF</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @elseif(($filters['type'] ?? '') === 'deposits')
        <div
            class="rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 mb-6 w-full">
            <div class="flex items-center justify-between px-6 pt-6 pb-2">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Dépôts clients</h2>
            </div>
            <div class="overflow-x-auto mt-4">
                <table class="w-full min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Date</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Client</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Montant</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($results['deposits'] ?? [] as $deposit)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $deposit->deposited_at ? \Carbon\Carbon::parse($deposit->deposited_at)->format('d/m/Y') : '' }}
                                </td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $deposit->customer->name ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ number_format($deposit->amount, 0, ',', ' ') }} XOF</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-gray-400 dark:text-gray-500 py-4">Aucun
                                    dépôt trouvé pour ces filtres.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 dark:bg-gray-800 font-bold">
                            <td colspan="2" class="px-4 py-2 text-right">Total</td>
                            <td class="px-4 py-2">
                                {{ number_format(collect($results['deposits'] ?? [])->sum('amount'), 0, ',', ' ') }}
                                XOF</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @elseif(($filters['type'] ?? '') === 'purchases')
        <div
            class="rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 mb-6 w-full">
            <div class="flex items-center justify-between px-6 pt-6 pb-2">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Achats</h2>
            </div>
            <div class="overflow-x-auto mt-4">
                <table class="w-full min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Date</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Fournisseur</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Montant total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($results['purchases'] ?? [] as $purchase)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $purchase->purchased_at ? \Carbon\Carbon::parse($purchase->purchased_at)->format('d/m/Y') : '' }}
                                </td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $purchase->provider->name ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ number_format($purchase->total, 0, ',', ' ') }} XOF</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-gray-400 dark:text-gray-500 py-4">Aucun
                                    achat trouvé pour ces filtres.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 dark:bg-gray-800 font-bold">
                            <td colspan="2" class="px-4 py-2 text-right">Total</td>
                            <td class="px-4 py-2">
                                {{ number_format(collect($results['purchases'] ?? [])->sum('total'), 0, ',', ' ') }}
                                XOF</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @elseif(($filters['type'] ?? '') === 'stock')
        <div
            class="rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 mb-6 w-full">
            <div class="flex items-center justify-between px-6 pt-6 pb-2">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Mouvements de stock</h2>
            </div>
            <div class="overflow-x-auto mt-4">
                <table class="w-full min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Date</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Produit</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Unité</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Type</th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Quantité</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($results['stock'] ?? [] as $stock)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $stock->created_at ? \Carbon\Carbon::parse($stock->created_at)->format('d/m/Y H:i') : '' }}
                                </td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $stock->productUnit->product->name ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $stock->productUnit->unit_name ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ __($stock->type) }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $stock->quantity }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-400 dark:text-gray-500 py-4">Aucun
                                    mouvement trouvé pour ces filtres.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 dark:bg-gray-800 font-bold">
                            <td colspan="4" class="px-4 py-2 text-right">Total mouvements</td>
                            <td class="px-4 py-2">
                                {{ number_format(collect($results['stock'] ?? [])->sum('quantity'), 0, ',', ' ') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
            <h2 class="text-lg font-semibold mb-2 text-gray-900 dark:text-gray-100">Top 5 produits les plus vendus</h2>
            <canvas id="topProductsChart" height="180"></canvas>
            <ul class="mt-4 text-sm text-gray-700 dark:text-gray-200">
                @foreach ($topProducts as $prod)
                    <li class="flex justify-between border-b border-gray-100 dark:border-gray-800 py-1">
                        <span>{{ $prod->product->name ?? '-' }}</span>
                        <span class="font-bold">{{ $prod->total_quantity }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
            <h2 class="text-lg font-semibold mb-2 text-gray-900 dark:text-gray-100">Top 5 produits les moins vendus
            </h2>
            <canvas id="bottomProductsChart" height="180"></canvas>
            <ul class="mt-4 text-sm text-gray-700 dark:text-gray-200">
                @foreach ($bottomProducts as $prod)
                    <li class="flex justify-between border-b border-gray-100 dark:border-gray-800 py-1">
                        <span>{{ $prod->product->name ?? '-' }}</span>
                        <span class="font-bold">{{ $prod->total_quantity }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
            <h2 class="text-lg font-semibold mb-2 text-gray-900 dark:text-gray-100">Top 5 CA par produit</h2>
            <canvas id="topCAProductsChart" height="180"></canvas>
            <ul class="mt-4 text-sm text-gray-700 dark:text-gray-200">
                @foreach ($topCAProducts as $prod)
                    <li class="flex justify-between border-b border-gray-100 dark:border-gray-800 py-1">
                        <span>{{ $prod->product->name ?? '-' }}</span>
                        <span class="font-bold">{{ number_format($prod->total_ca, 0, ',', ' ') }} XOF</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
            <h2 class="text-lg font-semibold mb-2 text-gray-900 dark:text-gray-100">Évolution mensuelle du CA</h2>
            <canvas id="monthlyCAChart" height="180"></canvas>
            <ul class="mt-4 text-sm text-gray-700 dark:text-gray-200">
                @foreach ($monthlyCAChart['labels'] as $i => $month)
                    <li class="flex justify-between border-b border-gray-100 dark:border-gray-800 py-1">
                        <span>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}</span>
                        <span class="font-bold">{{ number_format($monthlyCAChart['data'][$i], 0, ',', ' ') }}
                            XOF</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-filament::page>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const topCAProductsChart = document.getElementById('topCAProductsChart');
        if (topCAProductsChart) {
            new Chart(topCAProductsChart, {
                type: 'bar',
                data: {
                    labels: @json($topCAProductsChart['labels']),
                    datasets: [{
                        label: 'CA (XOF)',
                        data: @json($topCAProductsChart['data']),
                        backgroundColor: 'rgba(251,191,36,0.7)', // jaune
                        borderRadius: 8,
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        const monthlyCAChart = document.getElementById('monthlyCAChart');
        if (monthlyCAChart) {
            new Chart(monthlyCAChart, {
                type: 'line',
                data: {
                    labels: @json($monthlyCAChart['labels']),
                    datasets: [{
                        label: 'CA mensuel (XOF)',
                        data: @json($monthlyCAChart['data']),
                        fill: true,
                        borderColor: 'rgba(59,130,246,1)',
                        backgroundColor: 'rgba(59,130,246,0.15)',
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: 'rgba(59,130,246,1)',
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
@endpush
