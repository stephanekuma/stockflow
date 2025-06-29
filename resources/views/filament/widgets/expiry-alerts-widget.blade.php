<x-filament-widgets::widget>
    <x-filament::section>
        <!-- En-tête du widget -->
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Alertes de Péremption</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Produits périssables à surveiller</p>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Produits expirés -->
            <div
                class="group relative bg-gradient-to-br from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 border border-red-200 dark:border-red-800 overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-20 h-20 bg-red-100 dark:bg-red-800/30 rounded-full -translate-y-10 translate-x-10">
                </div>
                <div class="relative p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-red-100 dark:bg-red-800/30 rounded-lg">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-red-600 dark:text-red-400 font-medium">Urgent</div>
                        </div>
                    </div>
                    <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Expirés</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $this->getExpiredProductsCount() }}</div>
                    <div class="text-red-600 dark:text-red-400 font-semibold">Produits</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-2">À retirer</div>
                </div>
            </div>

            <!-- Produits expirant bientôt -->
            <div
                class="group relative bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 border border-yellow-200 dark:border-yellow-800 overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-20 h-20 bg-yellow-100 dark:bg-yellow-800/30 rounded-full -translate-y-10 translate-x-10">
                </div>
                <div class="relative p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-yellow-100 dark:bg-yellow-800/30 rounded-lg">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                </path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-yellow-600 dark:text-yellow-400 font-medium">Attention</div>
                        </div>
                    </div>
                    <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Expirent bientôt</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $this->getExpiringSoonProductsCount() }}</div>
                    <div class="text-yellow-600 dark:text-yellow-400 font-semibold">Produits</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-2">À surveiller</div>
                </div>
            </div>

            <!-- Total produits périssables -->
            <div
                class="group relative bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 border border-blue-200 dark:border-blue-800 overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-20 h-20 bg-blue-100 dark:bg-blue-800/30 rounded-full -translate-y-10 translate-x-10">
                </div>
                <div class="relative p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-blue-100 dark:bg-blue-800/30 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                </path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">Total</div>
                        </div>
                    </div>
                    <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Périssables</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $this->getTotalPerishableProducts() }}</div>
                    <div class="text-blue-600 dark:text-blue-400 font-semibold">Produits</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-2">En stock</div>
                </div>
            </div>
        </div>

        <!-- Produits expirés -->
        @if ($this->getExpiredProductsCount() > 0)
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-800 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-100 dark:bg-red-800/30 rounded-lg">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-red-900 dark:text-red-100">Produits Expirés</h4>
                            <p class="text-sm text-red-600 dark:text-red-400">À retirer immédiatement</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-red-50 dark:bg-red-900/30">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-red-700 dark:text-red-300 uppercase tracking-wider">
                                    Produit</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-red-700 dark:text-red-300 uppercase tracking-wider">
                                    Catégorie</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-red-700 dark:text-red-300 uppercase tracking-wider">
                                    Date d'expiration</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-red-700 dark:text-red-300 uppercase tracking-wider">
                                    Jours d'expiration</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-red-200 dark:divide-red-800">
                            @foreach ($this->getExpiredProducts() as $product)
                                <tr class="hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="p-1 bg-red-100 dark:bg-red-800/30 rounded">
                                                <svg class="w-3 h-3 text-red-600 dark:text-red-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $product->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $product->sku }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $product->category?->name ?? 'N/A' }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 font-medium">
                                        {{ $product->expiry_date?->format('d/m/Y') ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400">
                                            {{ $product->getDaysUntilExpiry() ?? 0 }} jours
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Produits expirant bientôt -->
        @if ($this->getExpiringSoonProductsCount() > 0)
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-yellow-200 dark:border-yellow-800 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-yellow-100 dark:bg-yellow-800/30 rounded-lg">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-yellow-900 dark:text-yellow-100">Produits Expirant Bientôt
                            </h4>
                            <p class="text-sm text-yellow-600 dark:text-yellow-400">À surveiller de près</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-yellow-50 dark:bg-yellow-900/30">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-yellow-700 dark:text-yellow-300 uppercase tracking-wider">
                                    Produit</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-yellow-700 dark:text-yellow-300 uppercase tracking-wider">
                                    Catégorie</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-yellow-700 dark:text-yellow-300 uppercase tracking-wider">
                                    Date d'expiration</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-yellow-700 dark:text-yellow-300 uppercase tracking-wider">
                                    Jours restants</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-yellow-200 dark:divide-yellow-800">
                            @foreach ($this->getExpiringSoonProducts() as $product)
                                <tr
                                    class="hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="p-1 bg-yellow-100 dark:bg-yellow-800/30 rounded">
                                                <svg class="w-3 h-3 text-yellow-600 dark:text-yellow-400"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                                    </path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $product->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $product->sku }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $product->category?->name ?? 'N/A' }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600 dark:text-yellow-400 font-medium">
                                        {{ $product->expiry_date?->format('d/m/Y') ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-400">
                                            {{ $product->getDaysUntilExpiry() ?? 0 }} jours
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Message si aucun produit périssable -->
        @if ($this->getExpiredProductsCount() === 0 && $this->getExpiringSoonProductsCount() === 0)
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="p-3 bg-green-100 dark:bg-green-800/30 rounded-full">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">Aucune alerte de péremption</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Tous vos produits périssables sont en bon
                            état</p>
                    </div>
                </div>
            </div>
        @endif
    </x-filament::section>

    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .group:hover {
            transform: translateY(-2px);
        }

        tbody tr {
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
        }
    </style>
</x-filament-widgets::widget>
