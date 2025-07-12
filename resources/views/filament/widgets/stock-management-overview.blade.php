<x-filament-widgets::widget>
    <x-filament::section>
        <!-- En-tête du widget -->
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aperçu des Mouvements de Stock</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Statistiques des 7 derniers jours</p>
                </div>
            </div>
        </div>

        <!-- Cartes de statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Retours -->
            <div
                class="group relative bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 border border-green-200 dark:border-green-800 overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-20 h-20 bg-green-100 dark:bg-green-800/30 rounded-full -translate-y-10 translate-x-10">
                </div>
                <div class="relative p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-green-100 dark:bg-green-800/30 rounded-lg">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-green-600 dark:text-green-400 font-medium">
                                +{{ $this->getReturnsCount() > 0 ? '100' : '0' }}%</div>
                        </div>
                    </div>
                    <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Retours</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $this->getReturnsCount() }}
                    </div>
                    <div class="text-green-600 dark:text-green-400 font-semibold">
                        {{ number_format($this->getReturnsTotal(), 0, ',', ' ') }} XOF</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-2">7 derniers jours</div>
                </div>
            </div>

            <!-- Pertes -->
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
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                </path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-red-600 dark:text-red-400 font-medium">
                                -{{ $this->getLossesCount() > 0 ? '100' : '0' }}%</div>
                        </div>
                    </div>
                    <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Pertes</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $this->getLossesCount() }}
                    </div>
                    <div class="text-red-600 dark:text-red-400 font-semibold">
                        {{ number_format($this->getLossesTotal(), 0, ',', ' ') }} XOF</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-2">7 derniers jours</div>
                </div>
            </div>

            <!-- Mouvements -->
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
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                +{{ $this->getHistoriesCount() > 0 ? '100' : '0' }}%</div>
                        </div>
                    </div>
                    <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Mouvements</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $this->getHistoriesCount() }}
                    </div>
                    <div class="text-blue-600 dark:text-blue-400 font-semibold">Total</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-2">7 derniers jours</div>
                </div>
            </div>
        </div>

        <!-- Tableau des derniers mouvements -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 dark:bg-purple-800/30 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">Derniers Mouvements</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Activité récente du stock</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                        </path>
                                    </svg>
                                    Type
                                </div>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    Date
                                </div>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                    Utilisateur
                                </div>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    Détail
                                </div>
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <div class="flex items-center gap-2 justify-end">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1">
                                        </path>
                                    </svg>
                                    Montant/Qté
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->getRecentMovements() as $index => $mvt)
                            <tr class="hover:bg-gray-50 dark:hover:bg-red-900 transition-colors duration-200"
                                style="animation-delay: {{ $index * 50 }}ms;">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if ($mvt['type'] === 'Retour')
                                            <div class="p-1 bg-green-100 dark:bg-green-800/30 rounded">
                                                <svg class="w-3 h-3 text-green-600 dark:text-green-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6">
                                                    </path>
                                                </svg>
                                            </div>
                                        @elseif($mvt['type'] === 'Perte')
                                            <div class="p-1 bg-red-100 dark:bg-red-800/30 rounded">
                                                <svg class="w-3 h-3 text-red-600 dark:text-red-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                                    </path>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="p-1 bg-blue-100 dark:bg-blue-800/30 rounded">
                                                <svg class="w-3 h-3 text-blue-600 dark:text-blue-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                                </svg>
                                            </div>
                                        @endif
                                        <span
                                            class="text-sm font-medium text-gray-900 dark:text-white">{{ $mvt['type'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($mvt['date'])->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $mvt['user'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <div class="max-w-xs truncate">{{ $mvt['details'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if (is_numeric($mvt['amount']))
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $mvt['amount'] > 0 ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400' }}">
                                            {{ number_format($mvt['amount'], 0, ',', ' ') }}
                                        </span>
                                    @else
                                        <span class="text-gray-900 dark:text-white">{{ $mvt['amount'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-full">
                                            <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 dark:text-gray-400 font-medium">Aucun mouvement
                                                récent</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500">Les mouvements
                                                apparaîtront ici</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
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
