<div>
    <h3 class="font-bold text-lg mb-2">Aperçu de la vente</h3>
    <div class="mb-2">
        <strong>Produits vendus :</strong>
        <ul class="list-disc ml-6">
            @foreach($products as $product)
                <li>
                    {{ $product['quantity'] }} x {{ $product['price'] }} XOF
                    @if(isset($product['discount']) && $product['discount'] > 0)
                        (Remise :
                        @if(($product['discount_type'] ?? 'amount') === 'percent')
                            {{ $product['discount'] }}%
                        @else
                            {{ $product['discount'] }} XOF
                        @endif
                        )
                    @endif
                    = <strong>{{ $product['total'] }} XOF</strong>
                </li>
            @endforeach
        </ul>
    </div>
    <div class="mb-2">
        <strong>Paiements :</strong>
        <ul class="list-disc ml-6">
            @foreach($payments as $payment)
                <li>
                    {{ ucfirst($payment['type']) }} : {{ $payment['amount'] }} XOF
                    @if(!empty($payment['note']))<em>({{ $payment['note'] }})</em>@endif
                </li>
            @endforeach
        </ul>
    </div>
    <div class="mb-2">
        <strong>Total :</strong> {{ $total }} XOF<br>
        <strong>Total payé :</strong> {{ $totalPaid }} XOF<br>
        <strong>Reste à payer :</strong> {{ $amountDue }} XOF
    </div>
    <div class="text-sm text-gray-500">Validez pour enregistrer la vente, ou modifiez si besoin.</div>
</div>
