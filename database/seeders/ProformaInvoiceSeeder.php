<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Models\Store;
use App\Models\Customer;
use App\Models\ProductUnit;
use App\Models\Pack;

class ProformaInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();
        $customers = Customer::all();
        $productUnits = ProductUnit::all();
        $packs = Pack::all();

        if ($stores->isEmpty() || $customers->isEmpty() || $productUnits->isEmpty()) {
            $this->command->warn('Skipping ProformaInvoiceSeeder: Missing required data (stores, customers, or product units)');
            return;
        }

        // Créer quelques factures proforma d'exemple
        for ($i = 1; $i <= 5; $i++) {
            $store = $stores->random();
            $customer = $customers->where('store_id', $store->id)->random();

            $proforma = ProformaInvoice::create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'invoice_number' => 'PRO-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'issued_at' => now()->subDays(rand(1, 30)),
                'valid_until' => now()->addDays(rand(7, 60)),
                'subtotal' => 0,
                'total' => 0,
                'discount' => 0,
                'notes' => 'Facture proforma d\'exemple #' . $i,
                'status' => ['draft', 'sent', 'accepted', 'rejected'][rand(0, 3)],
            ]);

            // Ajouter 1 à 3 éléments à chaque facture proforma
            $numItems = rand(1, 3);
            $subtotal = 0;
            $totalDiscount = 0;

            for ($j = 1; $j <= $numItems; $j++) {
                $quantity = rand(1, 10);
                $price = rand(100, 5000);
                $discount = rand(0, $price * 0.2); // Max 20% discount
                $total = ($quantity * $price) - $discount;

                // Choisir aléatoirement entre produit et pack
                if (rand(0, 1) === 0 && $productUnits->where('store_id', $store->id)->isNotEmpty()) {
                    $productUnit = $productUnits->where('store_id', $store->id)->random();

                    ProformaInvoiceItem::create([
                        'proforma_invoice_id' => $proforma->id,
                        'product_unit_id' => $productUnit->id,
                        'pack_id' => null,
                        'quantity' => $quantity,
                        'price' => $price,
                        'discount' => $discount,
                        'total' => $total,
                    ]);
                } elseif ($packs->where('store_id', $store->id)->isNotEmpty()) {
                    $pack = $packs->where('store_id', $store->id)->random();

                    ProformaInvoiceItem::create([
                        'proforma_invoice_id' => $proforma->id,
                        'product_unit_id' => null,
                        'pack_id' => $pack->id,
                        'quantity' => $quantity,
                        'price' => $price,
                        'discount' => $discount,
                        'total' => $total,
                    ]);
                }

                $subtotal += $quantity * $price;
                $totalDiscount += $discount;
            }

            // Mettre à jour les totaux
            $proforma->update([
                'subtotal' => $subtotal,
                'discount' => $totalDiscount,
                'total' => $subtotal - $totalDiscount,
            ]);
        }

        $this->command->info('ProformaInvoiceSeeder completed successfully!');
    }
}
