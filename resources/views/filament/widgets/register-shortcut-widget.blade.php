<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Gestion de la Caisse</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Accès rapide à la caisse</p>
                </div>
            </div>

            <div>
                @if ($latestRegister)
                    <button wire:click="confirmAction"
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        {{ __('Fermer la caisse') }}
                    </button>
                @else
                    <button wire:click="confirmAction"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        {{ __('Créer une caisse') }}
                    </button>
                @endif
            </div>
        </div>

        @if ($latestRegister)
            <div
                class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm text-green-700 dark:text-green-300">
                        {{ __('Caisse active :') }} <strong>{{ $latestRegister->name }}</strong>
                        <br>
                        <span class="text-xs">Solde actuel : {{ number_format($latestRegister->current_balance) }}
                            XOF</span>
                    </span>
                </div>
            </div>
        @else
            <div
                class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                        </path>
                    </svg>
                    <span class="text-sm text-yellow-700 dark:text-yellow-300">
                        {{ __('Aucune caisse créée. Créez-en une pour commencer.') }}
                    </span>
                </div>
            </div>
        @endif

        @if ($confirming)
            <div class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                    <h2 class="text-lg font-semibold mb-4">
                        @if ($actionType === 'open')
                            {{ __('Créer une nouvelle caisse ?') }}
                        @else
                            {{ __('Fermer la caisse ?') }}
                        @endif
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        @if ($actionType === 'open')
                            {{ __('Une nouvelle caisse sera créée avec le nom du jour et un solde initial de 10,000 XOF.') }}
                        @else
                            {{ __('La caisse actuelle sera fermée. Vous pourrez en créer une nouvelle demain.') }}
                        @endif
                    </p>
                    <div class="flex justify-end gap-2 mt-4">
                        <button wire:click="$set('confirming', false)"
                            class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">{{ __('Annuler') }}</button>
                        <button wire:click="toggleRegister"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            @if ($actionType === 'open')
                                {{ __('Créer') }}
                            @else
                                {{ __('Fermer') }}
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
