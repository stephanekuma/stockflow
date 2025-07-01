<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Provider;
use App\Models\CustomerDebt;
use App\Models\ProviderDebt;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\SalePayment;
use App\Models\ProviderPayment;

class TestDebtManagement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:debt-management';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test du système de gestion des dettes clients et fournisseurs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Test du système de gestion des dettes ===');

        // Test 1: Vérifier les dettes clients existantes
        $this->info("\n1. Dettes clients existantes:");
        $customerDebts = CustomerDebt::with(['customer', 'sale'])->get();
        if ($customerDebts->isEmpty()) {
            $this->warn('Aucune dette client trouvée.');
        } else {
            foreach ($customerDebts as $debt) {
                $this->line("- Client: {$debt->customer->name} | Vente: {$debt->sale->invoice_number} | Montant: {$debt->amount} | Payé: {$debt->paid} | Statut: {$debt->status}");
            }
        }

        // Test 2: Vérifier les dettes fournisseurs existantes
        $this->info("\n2. Dettes fournisseurs existantes:");
        $providerDebts = ProviderDebt::with(['provider', 'purchase'])->get();
        if ($providerDebts->isEmpty()) {
            $this->warn('Aucune dette fournisseur trouvée.');
        } else {
            foreach ($providerDebts as $debt) {
                $this->line("- Fournisseur: {$debt->provider->name} | Achat: {$debt->purchase->invoice_number} | Montant: {$debt->amount} | Payé: {$debt->paid} | Statut: {$debt->status}");
            }
        }

        // Test 3: Vérifier les paiements clients
        $this->info("\n3. Paiements clients récents:");
        $salePayments = SalePayment::with(['customer', 'sale'])->latest()->limit(5)->get();
        if ($salePayments->isEmpty()) {
            $this->warn('Aucun paiement client trouvé.');
        } else {
            foreach ($salePayments as $payment) {
                $this->line("- Client: {$payment->customer->name} | Vente: {$payment->sale->invoice_number} | Montant: {$payment->amount} | Type: {$payment->type}");
            }
        }

        // Test 4: Vérifier les paiements fournisseurs
        $this->info("\n4. Paiements fournisseurs récents:");
        $providerPayments = ProviderPayment::with(['provider', 'debt'])->latest()->limit(5)->get();
        if ($providerPayments->isEmpty()) {
            $this->warn('Aucun paiement fournisseur trouvé.');
        } else {
            foreach ($providerPayments as $payment) {
                $this->line("- Fournisseur: {$payment->provider->name} | Dette: #{$payment->provider_debt_id} | Montant: {$payment->amount} | Méthode: {$payment->method}");
            }
        }

        // Test 5: Statistiques générales
        $this->info("\n5. Statistiques générales:");
        $totalCustomerDebts = CustomerDebt::sum('amount');
        $totalCustomerPaid = CustomerDebt::sum('paid');
        $totalCustomerRemaining = $totalCustomerDebts - $totalCustomerPaid;

        $totalProviderDebts = ProviderDebt::sum('amount');
        $totalProviderPaid = ProviderDebt::sum('paid');
        $totalProviderRemaining = $totalProviderDebts - $totalProviderPaid;

        $this->line("Dettes clients: {$totalCustomerDebts} XOF (payé: {$totalCustomerPaid} XOF, reste: {$totalCustomerRemaining} XOF)");
        $this->line("Dettes fournisseurs: {$totalProviderDebts} XOF (payé: {$totalProviderPaid} XOF, reste: {$totalProviderRemaining} XOF)");

        // Test 6: Vérifier les dettes en retard
        $this->info("\n6. Dettes en retard:");
        $overdueCustomerDebts = CustomerDebt::where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->with('customer')
            ->get();

        $overdueProviderDebts = ProviderDebt::where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->with('provider')
            ->get();

        if ($overdueCustomerDebts->isEmpty() && $overdueProviderDebts->isEmpty()) {
            $this->info('Aucune dette en retard.');
        } else {
            if (!$overdueCustomerDebts->isEmpty()) {
                $this->warn("Dettes clients en retard ({$overdueCustomerDebts->count()}):");
                foreach ($overdueCustomerDebts as $debt) {
                    $this->line("- Client: {$debt->customer->name} | Échéance: {$debt->due_date} | Reste: " . ($debt->amount - $debt->paid) . " XOF");
                }
            }

            if (!$overdueProviderDebts->isEmpty()) {
                $this->warn("Dettes fournisseurs en retard ({$overdueProviderDebts->count()}):");
                foreach ($overdueProviderDebts as $debt) {
                    $this->line("- Fournisseur: {$debt->provider->name} | Échéance: {$debt->due_date} | Reste: " . ($debt->amount - $debt->paid) . " XOF");
                }
            }
        }

        $this->info("\n=== Test terminé ===");
    }
}
