<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\ProductUnit;
use Illuminate\Database\Seeder;
use Filament\Facades\Filament;

class PerishableProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer le store actuel
        $store = Filament::getTenant();

        if (!$store) {
            $this->command->error('No store found. Please set a tenant first.');
            return;
        }

        // Créer des catégories pour les produits périssables
        $categories = [
            'Lait et Produits Laitiers' => Category::firstOrCreate([
                'name' => 'Lait et Produits Laitiers',
                'store_id' => $store->id,
            ]),
            'Viandes et Charcuteries' => Category::firstOrCreate([
                'name' => 'Viandes et Charcuteries',
                'store_id' => $store->id,
            ]),
            'Fruits et Légumes' => Category::firstOrCreate([
                'name' => 'Fruits et Légumes',
                'store_id' => $store->id,
            ]),
            'Produits Surgelés' => Category::firstOrCreate([
                'name' => 'Produits Surgelés',
                'store_id' => $store->id,
            ]),
        ];

        // Créer des marques
        $brands = [
            'Fresco' => Brand::firstOrCreate([
                'name' => 'Fresco',
                'store_id' => $store->id,
            ]),
            'BioNature' => Brand::firstOrCreate([
                'name' => 'BioNature',
                'store_id' => $store->id,
            ]),
            'FreshFood' => Brand::firstOrCreate([
                'name' => 'FreshFood',
                'store_id' => $store->id,
            ]),
        ];

        // Créer des unités
        $units = [
            'Litre' => Unit::firstOrCreate(['name' => 'Litre', 'store_id' => $store->id]),
            'Kilogramme' => Unit::firstOrCreate(['name' => 'Kilogramme', 'store_id' => $store->id]),
            'Pièce' => Unit::firstOrCreate(['name' => 'Pièce', 'store_id' => $store->id]),
            'Pack' => Unit::firstOrCreate(['name' => 'Pack', 'store_id' => $store->id]),
        ];

        // Produits périssables avec différentes dates d'expiration
        $perishableProducts = [
            [
                'name' => 'Lait Frais Fresco',
                'sku' => 'LAIT001',
                'description' => 'Lait frais pasteurisé',
                'category' => $categories['Lait et Produits Laitiers'],
                'brand' => $brands['Fresco'],
                'is_perishable' => true,
                'expiry_date' => now()->addDays(5), // Expire bientôt
                'expiry_alert_days' => 7,
                'unit' => $units['Litre'],
                'quantity' => 1,
                'cost_price' => 800,
                'price' => 1200,
            ],
            [
                'name' => 'Yaourt Nature BioNature',
                'sku' => 'YAOURT001',
                'description' => 'Yaourt nature bio',
                'category' => $categories['Lait et Produits Laitiers'],
                'brand' => $brands['BioNature'],
                'is_perishable' => true,
                'expiry_date' => now()->subDays(2), // Déjà expiré
                'expiry_alert_days' => 5,
                'unit' => $units['Pièce'],
                'quantity' => 1,
                'cost_price' => 150,
                'price' => 250,
            ],
            [
                'name' => 'Steak de Bœuf Premium',
                'sku' => 'STEAK001',
                'description' => 'Steak de bœuf frais',
                'category' => $categories['Viandes et Charcuteries'],
                'brand' => $brands['FreshFood'],
                'is_perishable' => true,
                'expiry_date' => now()->addDays(15), // Bon état
                'expiry_alert_days' => 10,
                'unit' => $units['Kilogramme'],
                'quantity' => 1,
                'cost_price' => 3500,
                'price' => 4500,
            ],
            [
                'name' => 'Tomates Fraîches',
                'sku' => 'TOMATE001',
                'description' => 'Tomates fraîches du marché',
                'category' => $categories['Fruits et Légumes'],
                'brand' => $brands['BioNature'],
                'is_perishable' => true,
                'expiry_date' => now()->addDays(3), // Expire très bientôt
                'expiry_alert_days' => 5,
                'unit' => $units['Kilogramme'],
                'quantity' => 1,
                'cost_price' => 800,
                'price' => 1200,
            ],
            [
                'name' => 'Pizza Surgelée 4 Fromages',
                'sku' => 'PIZZA001',
                'description' => 'Pizza surgelée 4 fromages',
                'category' => $categories['Produits Surgelés'],
                'brand' => $brands['Fresco'],
                'is_perishable' => true,
                'expiry_date' => now()->addDays(60), // Longue durée
                'expiry_alert_days' => 30,
                'unit' => $units['Pièce'],
                'quantity' => 1,
                'cost_price' => 1200,
                'price' => 1800,
            ],
            [
                'name' => 'Fromage Blanc Nature',
                'sku' => 'FROMAGE001',
                'description' => 'Fromage blanc nature frais',
                'category' => $categories['Lait et Produits Laitiers'],
                'brand' => $brands['BioNature'],
                'is_perishable' => true,
                'expiry_date' => now()->addDays(8), // Expire bientôt
                'expiry_alert_days' => 7,
                'unit' => $units['Pack'],
                'quantity' => 1,
                'cost_price' => 600,
                'price' => 900,
            ],
        ];

        foreach ($perishableProducts as $productData) {
            $product = Product::firstOrCreate([
                'sku' => $productData['sku'],
                'store_id' => $store->id,
            ], [
                'name' => $productData['name'],
                'description' => $productData['description'],
                'category_id' => $productData['category']->id,
                'brand_id' => $productData['brand']->id,
                'is_perishable' => $productData['is_perishable'],
                'expiry_date' => $productData['expiry_date'],
                'expiry_alert_days' => $productData['expiry_alert_days'],
                'expiry_status' => 'good', // Sera mis à jour automatiquement
            ]);

            // Créer l'unité du produit
            ProductUnit::firstOrCreate([
                'product_id' => $product->id,
                'unit_id' => $productData['unit']->id,
                'store_id' => $store->id,
            ], [
                'quantity' => $productData['quantity'],
                'cost_price' => $productData['cost_price'],
                'price' => $productData['price'],
            ]);

            // Mettre à jour le statut de péremption
            $product->updateExpiryStatus();
        }

        $this->command->info('Perishable products seeded successfully!');
    }
}
