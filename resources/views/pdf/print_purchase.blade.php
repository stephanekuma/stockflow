<x-filament-panels::page>

    <head>
        <title>Purchase From {{ $purchase->provider?->name }}</title>
    </head>

    <div
        style="background-color: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px; max-width: 800px; margin: 0 auto;">
        <!-- Header -->
        <table style="width: 100%; border-bottom: 1px solid #e5e7eb; padding-bottom: 16px;">
            <tr>
                <td>
                    <h1 style="font-size: 24px; font-weight: bold; color: #4a5568;">Invoice</h1>
                    <p style="color: #a0aec0;">Invoice #{{ $purchase->invoice_number ?? 'N/A' }}</p>
                    <p style="color: #a0aec0;">Date: {{ $purchase->purchased_at?->format('d/m/Y') ?? 'N/A' }}</p>
                </td>
                <td style="text-align: right;">
                    @if ($settings?->logo)
                        <img src="{{ $settings->logo }}" alt="Company Logo" style="height: 64px;">
                    @else
                        <div
                            style="height: 64px; width: 64px; background-color: #e5e7eb; display: inline-block; text-align: center; line-height: 64px; color: #9ca3af; font-weight: bold;">
                            LOGO
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Billing Information -->
        <table style="width: 100%; margin-top: 24px;">
            <tr>
                <td>
                    <h2 style="font-weight: bold; color: #4a5568;">Billed From</h2>
                    <p style="color: #718096;">{{ $purchase->provider?->name ?? 'N/A' }}</p>
                    <p style="color: #718096;">{{ $purchase->provider?->address ?? 'N/A' }}</p>
                    <p style="color: #718096;">Email: {{ $purchase->provider?->email ?? 'N/A' }}</p>
                    <p style="color: #718096;">Phone: {{ $purchase->provider?->phone ?? 'N/A' }}</p>
                </td>
                <td style="text-align: right;">
                    <h2 style="font-weight: bold; color: #4a5568;">Company</h2>
                    <p style="color: #718096;">{{ $purchase->store?->name ?? 'N/A' }}</p>
                    <p style="color: #718096;">{{ $purchase->store?->address ?? 'N/A' }}</p>
                    <p style="color: #718096;">Phone: {{ $purchase->store?->phone ?? 'N/A' }}</p>
                    <p style="color: #718096;">Email: {{ $purchase->store?->email ?? 'N/A' }}</p>
                </td>
            </tr>
        </table>

        <!-- Invoice Items -->
        <table style="width: 100%; margin-top: 24px; border-collapse: collapse; border: 1px solid #e2e8f0;">
            <thead>
                <tr style="background-color: #f7fafc;">
                    <th style="border: 1px solid #e2e8f0; padding: 8px; text-align: left; color: #4a5568;">Description
                    </th>
                    <th style="border: 1px solid #e2e8f0; padding: 8px; text-align: center; color: #4a5568;">Unit</th>
                    <th style="border: 1px solid #e2e8f0; padding: 8px; text-align: right; color: #4a5568;">Quantity
                    </th>
                    <th style="border: 1px solid #e2e8f0; padding: 8px; text-align: right; color: #4a5568;">Unit Price
                    </th>
                    <th style="border: 1px solid #e2e8f0; padding: 8px; text-align: right; color: #4a5568;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchase->purchasedProducts as $product)
                    <tr>
                        <td style="border: 1px solid #e2e8f0; padding: 8px; color: #718096;">
                            {{ $product->productUnit?->product?->name ?? 'N/A' }}</td>
                        <td style="border: 1px solid #e2e8f0; padding: 8px; text-align: center; color: #718096;">
                            {{ $product->productUnit?->unit?->name ?? 'N/A' }}</td>
                        <td style="border: 1px solid #e2e8f0; padding: 8px; text-align: right; color: #718096;">
                            {{ number_format($product->quantity, 2) }}</td>
                        <td style="border: 1px solid #e2e8f0; padding: 8px; text-align: right; color: #718096;">
                            {{ number_format($product->price, 2) }} FCFA</td>
                        <td style="border: 1px solid #e2e8f0; padding: 8px; text-align: right; color: #718096;">
                            {{ number_format($product->total, 2) }} FCFA</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"
                            style="border: 1px solid #e2e8f0; padding: 8px; text-align: center; color: #718096;">No
                            products found</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background-color: #f7fafc;">
                    <td colspan="4"
                        style="border: 1px solid #e2e8f0; padding: 8px; text-align: right; font-weight: bold; color: #4a5568;">
                        Subtotal</td>
                    <td style="border: 1px solid #e2e8f0; padding: 8px; text-align: right; color: #4a5568;">
                        {{ number_format($purchase->subtotal ?? 0, 2) }} FCFA</td>
                </tr>
                <tr>
                    <td colspan="4"
                        style="border: 1px solid #e2e8f0; padding: 8px; text-align: right; font-weight: bold; color: #4a5568;">
                        Discount</td>
                    <td style="border: 1px solid #e2e8f0; padding: 8px; text-align: right; color: #4a5568;">
                        {{ number_format($purchase->discount ?? 0, 2) }} FCFA</td>
                </tr>
                <tr style="background-color: #f7fafc;">
                    <td colspan="4"
                        style="border: 1px solid #e2e8f0; padding: 8px; text-align: right; font-weight: bold; color: #4a5568;">
                        Total</td>
                    <td
                        style="border: 1px solid #e2e8f0; padding: 8px; text-align: right; color: #4a5568; font-weight: bold;">
                        {{ number_format($purchase->total ?? 0, 2) }} FCFA</td>
                </tr>
            </tfoot>
        </table>

        @if ($purchase->notes)
            <!-- Notes -->
            <div style="margin-top: 24px; padding: 16px; background-color: #f7fafc; border-radius: 4px;">
                <h3 style="font-weight: bold; color: #4a5568; margin-bottom: 8px;">Notes</h3>
                <p style="color: #718096; margin: 0;">{{ $purchase->notes }}</p>
            </div>
        @endif

        <!-- Footer -->
        <div style="margin-top: 24px; text-align: center; color: #a0aec0;">
            <p>Thank you for your business!</p>
            <p>If you have any questions about this invoice, please contact us at
                {{ $purchase->store?->email ?? 'info@company.com' }}.</p>
            <p style="margin-top: 16px; font-size: 12px;">Document generated on {{ now()->format('d/m/Y at H:i') }}</p>
        </div>
    </div>

    <style>
        @media print {
            .print-content {
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .filament-header,
            .filament-sidebar,
            .filament-main-topbar,
            .filament-main-content-header {
                display: none !important;
            }

            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            div[style*="background-color: white"] {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 16px !important;
            }
        }
    </style>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('print', () => {
                window.print();
            });
        });
    </script>
</x-filament-panels::page>
