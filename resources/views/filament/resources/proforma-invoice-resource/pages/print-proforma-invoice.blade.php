<div class="cec-invoice-container">
    <!-- Header -->
    <div class="cec-header">
        <div class="cec-header-left">
            <h1 class="cec-title">FACTURE PROFORMA</h1>
            <div class="cec-invoice-details">
                <p><strong>N° Proforma :</strong> {{ $record->invoice_number ?? 'N/A' }}</p>
                <p><strong>Date d'émission :</strong> {{ $record->issued_at?->format('d/m/Y') ?? 'N/A' }}</p>
                <p><strong>Valide jusqu'au :</strong> {{ $record->valid_until?->format('d/m/Y') ?? 'N/A' }}</p>
                <p><strong>Statut :</strong> <span
                        class="status-badge status-{{ $record->status }}">{{ \App\Models\ProformaInvoice::getStatusOptions()[$record->status] ?? $record->status }}</span>
                </p>
            </div>
        </div>
        <div class="cec-header-right">
            @if ($record->store?->logo)
                <img src="{{ $record->store->logo }}" alt="Logo" class="cec-logo">
            @else
                <div class="cec-logo-placeholder">
                    <span>{{ $record->store->name ?? 'Entreprise' }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Company & Customer Information -->
    <div class="cec-company-info">
        <div class="cec-billed-from">
            <h2 class="cec-section-title">INFORMATIONS CLIENT</h2>
            <div class="cec-info-card">
                <p class="cec-company-name">{{ $record->customer?->name ?? 'N/A' }}</p>
                <p class="cec-address">{{ $record->customer?->address ?? 'N/A' }}</p>
                <p class="cec-contact">📧 {{ $record->customer?->email ?? 'N/A' }}</p>
                <p class="cec-contact">📞 {{ $record->customer?->phone ?? 'N/A' }}</p>
            </div>
        </div>
        <div class="cec-company-details">
            <h2 class="cec-section-title">INFORMATIONS ENTREPRISE</h2>
            <div class="cec-info-card">
                <p class="cec-company-name">{{ $record->store?->name ?? 'N/A' }}</p>
                <p class="cec-address">{{ $record->store?->address ?? 'N/A' }}</p>
                <p class="cec-contact">📞 {{ $record->store?->phone ?? 'N/A' }}</p>
                <p class="cec-contact">📧 {{ $record->store?->email ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- Invoice Items -->
    <div class="cec-items-section">
        <h2 class="cec-section-title">DÉTAILS DE LA PROFORMA</h2>
        <div class="cec-table-container">
            <table class="cec-table">
                <thead>
                    <tr>
                        <th class="cec-th">Produit</th>
                        <th class="cec-th">Unité</th>
                        <th class="cec-th">Quantité</th>
                        <th class="cec-th">Prix unitaire</th>
                        <th class="cec-th">Remise</th>
                        <th class="cec-th">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($record->items as $item)
                        <tr class="cec-tr">
                            <td class="cec-td">
                                @if ($item->isPack())
                                    <strong>{{ $item->pack->name ?? 'N/A' }}</strong>
                                @else
                                    {{ $item->productUnit->product->name ?? 'N/A' }}
                                @endif
                            </td>
                            <td class="cec-td text-center">
                                @if ($item->isPack())
                                    <span class="text-xs text-gray-500">Pack</span>
                                @else
                                    {{ $item->productUnit->unit->name ?? 'N/A' }}
                                @endif
                            </td>
                            <td class="cec-td text-right">{{ number_format($item->quantity, 2) }}</td>
                            <td class="cec-td text-right">{{ number_format($item->price, 2) }} FCFA</td>
                            <td class="cec-td text-right">{{ number_format($item->discount, 2) }} FCFA</td>
                            <td class="cec-td text-right">{{ number_format($item->total, 2) }} FCFA</td>
                        </tr>
                    @empty
                        <tr class="cec-tr">
                            <td colspan="6" class="cec-td text-center">Aucun produit</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Totals -->
    <div class="cec-totals-section">
        <div class="cec-totals-table">
            <div class="cec-total-row">
                <span class="cec-total-label">Sous-total :</span>
                <span class="cec-total-value">{{ number_format($record->subtotal ?? 0, 2) }} FCFA</span>
            </div>
            <div class="cec-total-row">
                <span class="cec-total-label">Remise totale :</span>
                <span class="cec-total-value">{{ number_format($record->discount ?? 0, 2) }} FCFA</span>
            </div>
            <div class="cec-total-row cec-total-final">
                <span class="cec-total-label">TOTAL :</span>
                <span class="cec-total-value">{{ number_format($record->total ?? 0, 2) }} FCFA</span>
            </div>
        </div>
    </div>

    @if ($record->notes)
        <!-- Notes -->
        <div class="cec-notes-section">
            <h3 class="cec-notes-title">Notes</h3>
            <div class="cec-notes-content">
                {{ $record->notes }}
            </div>
        </div>
    @endif

    <!-- Footer -->
    <div class="cec-footer">
        <div class="cec-footer-content">
            <p class="cec-thank-you">Merci pour votre confiance !</p>
            <p class="cec-contact-info">Pour toute question, contactez-nous à
                {{ $record->store?->email ?? 'info@entreprise.com' }}</p>
            <p class="cec-generated">Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
            <p class="cec-contact-info"><strong>IMPORTANT :</strong> Cette facture proforma n'est pas une facture
                définitive et ne constitue pas un engagement de vente.</p>
        </div>
    </div>

    <style>
        /* Copier ici tout le CSS cec-* des autres factures */
        .cec-invoice-container {
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            max-width: 900px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2d3748;
        }

        .cec-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3182ce;
        }

        .cec-title {
            font-size: 32px;
            font-weight: 700;
            color: #2b6cb0;
            margin: 0 0 15px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .cec-invoice-details {
            background: #ebf8ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3182ce;
        }

        .cec-invoice-details p {
            margin: 5px 0;
            font-size: 14px;
        }

        .cec-logo {
            height: 80px;
            width: auto;
        }

        .cec-logo-placeholder {
            height: 80px;
            width: 120px;
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            border-radius: 8px;
            font-weight: bold;
            font-size: 18px;
        }

        .cec-logo-placeholder small {
            font-size: 10px;
            margin-top: 5px;
            text-align: center;
        }

        .cec-company-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .cec-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2b6cb0;
            margin: 0 0 15px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cec-info-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .cec-company-name {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 10px 0;
        }

        .cec-address {
            color: #4a5568;
            margin: 5px 0;
            line-height: 1.5;
        }

        .cec-contact {
            color: #718096;
            margin: 5px 0;
            font-size: 14px;
        }

        .cec-items-section {
            margin-bottom: 30px;
        }

        .cec-table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .cec-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cec-th {
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cec-tr:nth-child(even) {
            background-color: #f7fafc;
        }

        .cec-tr:hover {
            background-color: #ebf8ff;
        }

        .cec-td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .cec-totals-section {
            margin-bottom: 30px;
        }

        .cec-totals-table {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .cec-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .cec-total-row:last-child {
            border-bottom: none;
        }

        .cec-total-label {
            font-weight: 500;
            color: #4a5568;
        }

        .cec-total-value {
            font-weight: 600;
            color: #2d3748;
        }

        .cec-total-final {
            background: #ebf8ff;
            margin: 0 -20px;
            padding: 15px 20px;
            border-top: 2px solid #3182ce;
            border-bottom: 2px solid #3182ce;
        }

        .cec-total-final .cec-total-label {
            font-size: 18px;
            font-weight: 700;
            color: #2b6cb0;
        }

        .cec-total-final .cec-total-value {
            font-size: 18px;
            font-weight: 700;
            color: #2b6cb0;
        }

        .cec-notes-section {
            background: #fef5e7;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .cec-notes-title {
            font-size: 16px;
            font-weight: 600;
            color: #c05621;
            margin: 0 0 10px 0;
        }

        .cec-notes-content {
            color: #744210;
            line-height: 1.6;
        }

        .cec-footer {
            text-align: center;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
        }

        .cec-thank-you {
            font-size: 18px;
            font-weight: 600;
            color: #2b6cb0;
            margin: 0 0 10px 0;
        }

        .cec-contact-info {
            color: #718096;
            margin: 5px 0;
        }

        .cec-generated {
            color: #a0aec0;
            font-size: 12px;
            margin: 15px 0 0 0;
        }

        @media print {

            header,
            nav,
            .header,
            .navbar,
            .navigation,
            .page-header,
            .breadcrumb,
            .page-title,
            .page-meta,
            .page-footer,
            .footer,
            .site-footer,
            .print-hide {
                display: none !important;
            }

            a[href]:after {
                content: none !important;
            }

            .page-date,
            .page-timestamp,
            .page-generated {
                display: none !important;
            }

            .cec-invoice-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 20px !important;
                margin: 0 !important;
            }

            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 12pt !important;
                line-height: 1.4 !important;
            }

            .cec-header {
                border-bottom: 2px solid #3182ce !important;
            }

            .cec-th {
                background: #3182ce !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
            }

            .cec-total-final {
                background: #ebf8ff !important;
                -webkit-print-color-adjust: exact;
            }

            .cec-notes-section {
                background: #fef5e7 !important;
                -webkit-print-color-adjust: exact;
            }

            .cec-invoice-container {
                display: block !important;
                position: relative !important;
                top: 0 !important;
                left: 0 !important;
            }
        }
    </style>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</div>
