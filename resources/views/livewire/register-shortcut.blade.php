<div>
    @if ($latestRegister)
        <button wire:click="confirmAction"
            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 focus:outline-none">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('Caisse disponible') }}
        </button>
    @else
        <button wire:click="confirmAction"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('Créer une caisse') }}
        </button>
    @endif

    @if ($confirming)
        <div class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                <h2 class="text-lg font-semibold mb-4">
                    @if ($actionType === 'open')
                        {{ __('Créer une nouvelle caisse ?') }}
                    @else
                        {{ __('Caisse disponible') }}
                    @endif
                </h2>
                <div class="flex justify-end gap-2 mt-4">
                    <button wire:click="$set('confirming', false)"
                        class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">{{ __('Annuler') }}</button>
                    <button wire:click="toggleRegister"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        @if ($actionType === 'open')
                            {{ __('Créer') }}
                        @else
                            {{ __('OK') }}
                        @endif
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($latestRegister)
        <div class="mt-2 text-xs text-gray-600 dark:text-gray-300">
            {{ __('Caisse :') }}
            <span class="font-bold text-green-600">
                {{ __('Disponible') }}
            </span>
        </div>
    @endif
</div>
