<div class="p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Testeur de Conversion d'Unités</h2>

    <!-- Sélection du produit et de l'unité -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Produit</label>
            <select wire:model.live="selectedProductId"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Sélectionner un produit</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Unité demandée</label>
            <select wire:model.live="selectedUnitId"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Sélectionner une unité</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Quantité</label>
            <input type="number" wire:model.live="quantity" min="0.01" step="0.01"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <!-- Unités disponibles pour le produit -->
    @if (count($availableUnits) > 0)
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3 text-gray-700">Unités disponibles pour ce produit</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($availableUnits as $unit)
                    <div class="bg-gray-50 p-4 rounded-lg border">
                        <div class="font-medium text-gray-800">{{ $unit['name'] }}</div>
                        <div class="text-sm text-gray-600">
                            Stock: {{ $unit['available_quantity'] }}<br>
                            Facteur de conversion: {{ $unit['conversion_factor'] }}<br>
                            Unité de base: {{ $unit['base_unit'] ?? 'Aucune' }}<br>
                            @if ($unit['is_custom_conversion'])
                                <span class="text-blue-600 font-medium">Conversion personnalisée</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Boutons d'action -->
    <div class="flex flex-wrap gap-4 mb-6">
        <button wire:click="getSellingStrategy"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            Obtenir la stratégie de vente optimale
        </button>

        <button wire:click="checkStock"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
            Vérifier le stock
        </button>
    </div>

    <!-- Résultats -->
    @if ($conversionResult)
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3 text-gray-700">Résultat</h3>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                @if (isset($conversionResult['from']))
                    <div class="text-blue-800">
                        <strong>Conversion:</strong> {{ $conversionResult['from'] }} = {{ $conversionResult['to'] }}
                    </div>
                @endif

                @if (isset($conversionResult['stock_check']))
                    <div class="text-blue-800">
                        <strong>Stock:</strong> {{ $conversionResult['stock_check'] }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($sellingStrategy)
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3 text-gray-700">Stratégie de vente optimale</h3>

            @if ($sellingStrategy['can_sell'])
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <div class="text-green-800 font-medium">✅ Vente possible</div>
                    <div class="text-green-700 text-sm">Quantité demandée en unité de base:
                        {{ $sellingStrategy['requested_in_base'] }}</div>
                </div>
            @else
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <div class="text-red-800 font-medium">❌ Stock insuffisant</div>
                    <div class="text-red-700 text-sm">Il manque {{ $sellingStrategy['missing_quantity'] }} unités de
                        base</div>
                </div>
            @endif

            @if (count($sellingStrategy['strategy']) > 0)
                <div class="space-y-3">
                    @foreach ($sellingStrategy['strategy'] as $item)
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="font-medium text-gray-800">{{ $item['unit_name'] }}</div>
                            <div class="text-sm text-gray-600">
                                Quantité à utiliser: {{ $item['quantity'] }}<br>
                                Quantité en unité de base: {{ $item['quantity_in_base'] }}<br>
                                Stock disponible: {{ $item['available_stock'] }}<br>
                                Facteur de conversion: {{ $item['conversion_factor'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- Test de conversion entre unités -->
    @if (count($availableUnits) > 1)
        <div class="mt-8">
            <h3 class="text-lg font-semibold mb-3 text-gray-700">Test de conversion entre unités</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($availableUnits as $fromUnit)
                    @foreach ($availableUnits as $toUnit)
                        @if ($fromUnit['id'] !== $toUnit['id'])
                            <button wire:click="convertQuantity({{ $fromUnit['id'] }}, {{ $toUnit['id'] }})"
                                class="text-left p-3 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <div class="font-medium text-gray-800">
                                    {{ $fromUnit['name'] }} → {{ $toUnit['name'] }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    Convertir {{ $quantity }} {{ $fromUnit['name'] }}
                                </div>
                            </button>
                        @endif
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    <!-- Messages d'erreur -->
    @if ($errors->any())
        <div class="mt-6">
            @foreach ($errors->all() as $error)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-2">
                    <div class="text-red-800">{{ $error }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>
