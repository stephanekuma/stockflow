<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Unit;
use App\Services\UnitConversionService;
use Illuminate\Console\Command;

class TestUnitConversion extends Command
{
    protected $signature = 'test:unit-conversion';
    protected $description = 'Test the unit conversion system with example data';

    public function handle()
    {
        $this->info('🧪 Test du système de conversion d\'unités');
        $this->newLine();

        // Récupérer les unités créées par le seeder
        $piece = Unit::where('key', 'piece')->first();
        $carton = Unit::where('key', 'carton')->first();

        if (!$piece || !$carton) {
            $this->error('❌ Unités de test non trouvées. Exécutez d\'abord le seeder.');
            return 1;
        }

        // Créer un produit de test
        $product = Product::first();
        if (!$product) {
            $this->error('❌ Aucun produit trouvé. Créez d\'abord un produit.');
            return 1;
        }

        // Configurer le stock du produit
        $this->setupProductStock($product, $piece, $carton);

        // Tester les conversions
        $this->testConversions($product, $piece, $carton);

        $this->info('✅ Tests terminés avec succès !');
        return 0;
    }

    private function setupProductStock(Product $product, Unit $piece, Unit $carton)
    {
        $this->info('📦 Configuration du stock de test...');

        // Supprimer les anciennes configurations
        ProductUnit::where('product_id', $product->id)->delete();

        // Créer le stock : 4 cartons + 0 pièces
        ProductUnit::create([
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'unit_id' => $carton->id,
            'quantity' => 4, // 4 cartons = 80 pièces en base
            'cost_price' => 100,
            'price' => 120,
        ]);

        ProductUnit::create([
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'unit_id' => $piece->id,
            'quantity' => 0, // 0 pièces individuelles
            'cost_price' => 5,
            'price' => 6,
        ]);

        $this->info("   - Stock configuré : 4 cartons + 0 pièces");
        $this->info("   - Total en unité de base : 80 pièces");
    }

    private function testConversions(Product $product, Unit $piece, Unit $carton)
    {
        $service = new UnitConversionService();

        $this->newLine();
        $this->info('🔄 Test des conversions...');

        // Test 1: Vendre 3 pièces
        $this->info('Test 1: Vendre 3 pièces');
        try {
            $strategy = $service->getOptimalSellingStrategy($product->id, $piece->id, 3);

            if ($strategy['can_sell']) {
                $this->info('   ✅ Vente possible');
                $this->info('   📊 Stratégie :');
                foreach ($strategy['strategy'] as $item) {
                    $this->info("      - {$item['unit_name']}: {$item['quantity']} (en base: {$item['quantity_in_base']})");
                }
            } else {
                $this->error("   ❌ Stock insuffisant. Il manque {$strategy['missing_quantity']} unités de base");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur : {$e->getMessage()}");
        }

        // Test 2: Vendre 25 pièces
        $this->newLine();
        $this->info('Test 2: Vendre 25 pièces');
        try {
            $strategy = $service->getOptimalSellingStrategy($product->id, $piece->id, 25);

            if ($strategy['can_sell']) {
                $this->info('   ✅ Vente possible');
                $this->info('   📊 Stratégie :');
                foreach ($strategy['strategy'] as $item) {
                    $this->info("      - {$item['unit_name']}: {$item['quantity']} (en base: {$item['quantity_in_base']})");
                }
            } else {
                $this->error("   ❌ Stock insuffisant. Il manque {$strategy['missing_quantity']} unités de base");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur : {$e->getMessage()}");
        }

        // Test 3: Vendre 1 carton
        $this->newLine();
        $this->info('Test 3: Vendre 1 carton');
        try {
            $strategy = $service->getOptimalSellingStrategy($product->id, $carton->id, 1);

            if ($strategy['can_sell']) {
                $this->info('   ✅ Vente possible');
                $this->info('   📊 Stratégie :');
                foreach ($strategy['strategy'] as $item) {
                    $this->info("      - {$item['unit_name']}: {$item['quantity']} (en base: {$item['quantity_in_base']})");
                }
            } else {
                $this->error("   ❌ Stock insuffisant. Il manque {$strategy['missing_quantity']} unités de base");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur : {$e->getMessage()}");
        }

        // Test 4: Vendre 2 cartons
        $this->newLine();
        $this->info('Test 4: Vendre 2 cartons');
        try {
            $strategy = $service->getOptimalSellingStrategy($product->id, $carton->id, 2);

            if ($strategy['can_sell']) {
                $this->info('   ✅ Vente possible');
                $this->info('   📊 Stratégie :');
                foreach ($strategy['strategy'] as $item) {
                    $this->info("      - {$item['unit_name']}: {$item['quantity']} (en base: {$item['quantity_in_base']})");
                }
            } else {
                $this->error("   ❌ Stock insuffisant. Il manque {$strategy['missing_quantity']} unités de base");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur : {$e->getMessage()}");
        }

        // Test 5: Conversion entre unités
        $this->newLine();
        $this->info('Test 5: Conversion entre unités');
        try {
            $convertedQuantity = $service->convertQuantity($product->id, 1, $carton->id, $piece->id);
            $this->info("   ✅ 1 carton = {$convertedQuantity} pièces");

            $convertedQuantity = $service->convertQuantity($product->id, 20, $piece->id, $carton->id);
            $this->info("   ✅ 20 pièces = {$convertedQuantity} carton(s)");
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur : {$e->getMessage()}");
        }

        // Test 6: Vérification du stock
        $this->newLine();
        $this->info('Test 6: Vérification du stock');
        try {
            $hasStock = $service->hasEnoughStock($product->id, 3, $piece->id);
            $this->info("   ✅ Stock suffisant pour 3 pièces : " . ($hasStock ? 'Oui' : 'Non'));

            $hasStock = $service->hasEnoughStock($product->id, 100, $piece->id);
            $this->info("   ✅ Stock suffisant pour 100 pièces : " . ($hasStock ? 'Oui' : 'Non'));
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur : {$e->getMessage()}");
        }
    }
}
