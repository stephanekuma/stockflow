@php
    $packId = $getState('pack_id');
@endphp

@if ($packId)
    <div class="pack-components" wire:key="pack-{{ $packId }}">
        <div class="text-sm font-medium text-gray-700 mb-2">
            {{ __('Pack Components') }} (ID: {{ $packId }}):
        </div>

        @php
            $pack = \App\Models\Pack::with(['packProducts.productUnit.product', 'packProducts.productUnit.unit'])
                ->where('store_id', \Filament\Facades\Filament::getTenant()->id)
                ->find($packId);
        @endphp

        @if ($pack)
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-600 mb-2">
                    <strong>{{ $pack->name }}</strong> - {{ number_format($pack->price, 2) }} XOF
                </div>

                @if ($pack->packProducts && $pack->packProducts->count() > 0)
                    <div class="space-y-1">
                        @foreach ($pack->packProducts as $component)
                            <div class="flex justify-between text-xs text-gray-600">
                                <span>
                                    • {{ $component->productUnit->product->name ?? 'N/A' }}
                                    ({{ $component->productUnit->unit->name ?? 'N/A' }})
                                </span>
                                <span class="font-medium">
                                    {{ $component->quantity }}x
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-xs text-gray-500 italic">
                        {{ __('No components found in this pack') }}
                    </div>
                @endif
            </div>
        @else
            <div class="text-xs text-red-500 italic">
                {{ __('Pack not found') }}
            </div>
        @endif
    </div>
@endif
